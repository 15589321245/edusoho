define(function(require, exports, module) {
    require('webuploader2');
    var store = require('store');
    var filesize = require('filesize');
    var Widget = require('widget');

    var hookRegisted = false;

    var BatchUploader = Widget.extend({

        uploader: null,

        attrs: {
            initUrl: null,
            finishUrl: null,
            uploadUrl: null,
            uploadProxyUrl: null,
            accept: null,
            process: 'none',
            uploadToken: null,
            multi: true
        },

        setup: function() {
            this._initUI();
            var accept = {};
            accept.title = '文件';
            accept.extensions = this.get('accept')['extensions'].join(',');
            accept.mimeTypes = this.get('accept')['mimeTypes'].join(',');

            var defaults = {
                runtimeOrder: 'html5,flash',

                dnd: this.element.find('.balloon-uploader-body'),
                accept: accept,

                // 不压缩image
                resize: false,

                // swf文件路径
                swf: CLOUD_FILE_SERVER + '/uploader.swf',

                // 选择文件的按钮。可选。
                // 内部根据当前运行是创建，可能是input元素，也可能是flash.
                pick: this.element.find('.file-pick-btn') ,
                threads: 1,
                formData: {

                }
            };

            if (!this.get('multi')) {
                defaults['fileNumLimit'] = 1;
            }

            this._initUploaderHook();
            var uploader = this.uploader = WebUploader.create(defaults);
            this._registerUploaderEvent(uploader);

            this.element.find('.start-upload-btn').on('click', function(){
                uploader.upload();
            });
        },

        destroy: function() {
            if (this.uploader) {
                this.uploader.stop();
                this.uploader.destroy();
            }
            BatchUploader.superclass.destroy.call(this);
        },

        _initUI: function() {
            var html = '';
            html += '<div class="balloon-uploader-heading">上传文件</div>';
            html += '<div class="balloon-uploader-body">';
            html += '  <div class="balloon-nofile">请将文件拖到这里，或点击添加文件按钮</div>';
            html += '  <div class="balloon-filelist">';
            html += '    <div class="balloon-filelist-heading">';
            html += '    <div class="file-name">文件名</div>';
            html += '    <div class="file-size">大小</div>';
            html += '    <div class="file-status">状态</div>';
            html += '  </div>';
            html += '  <ul></ul>';
            html += '</div>';
            html += '<div class="balloon-uploader-footer">';
            html += '  <div class="file-pick-btn"><i class="glyphicon glyphicon-plus"></i> 添加文件</div>';

            if (this.get('multi')) {
                html += '<div class="start-upload-btn"><i class="glyphicon glyphicon-upload"></i> 开始上传</div>';
            }

            html += '</div>';

            this.element.addClass('balloon-uploader');
            this.element.html(html);
        },

        

        _registerUploaderEvent: function(uploader) {
            var self = this;
            var $uploader = this.element;
            // 当有文件添加进来的时候
            uploader.on('fileQueued', function(file) {
                $uploader.find('.balloon-nofile').remove();
                var $list =$uploader.find('.balloon-filelist ul');
                $list.append(
                    '<li id="' + file.id + '">' +
                    '  <div class="file-name">' + file.name + '</div>' +
                    '  <div class="file-size">' + filesize(file.size) + '</div>' +
                    '  <div class="file-status">待上传</div>' +
                    '  <div class="file-progress"><div class="file-progress-bar" style="width: 0%;"></div></div>' +
                    '</li>'
                );

                self.trigger('file.queued', file);

                if (!self.get('multi')) {
                    uploader.upload();
                }

            });

            // 文件上传过程中创建进度条实时显示。
            uploader.on('uploadProgress', function(file, percentage) {
                var $li = $('#' + file.id);
                percentage = (percentage * 100).toFixed(2) + '%';
                $li.find('.file-status').html(percentage);
                $li.find('.file-progress-bar').css('width', percentage);
            });

            uploader.on('uploadSuccess', function(file) {
                var $li = $('#' + file.id);
                $li.find('.file-status').html('已上传');
                $li.find('.file-progress-bar').css('width', '0%');
                var key = 'file_' + file.hash;
                store.remove(key);
            });

            uploader.on('beforeFileQueued', function(file) {
                file.uploaderWidget = self;
            });

            uploader.on('uploadComplete', function(file) {
            });

            uploader.on('uploadAccept', function(object, ret) {
                var key = 'file_' + object.file.hash;
                var value = store.get(key);
                value[object.chunk] = ret;
                store.set(key, value);
                
                var strategy = self.get('strategy');
                strategy.uploadAccept(object, ret);
            });

            uploader.on('uploadStart', function(file) {
            });

            uploader.on('uploadBeforeSend', function(object, data, headers) {
                var strategy = self.get('strategy');
                strategy.uploadBeforeSend(object, data, headers);
            });
        },

        _getDirectives: function(file) {
            var extOutputs = {
                mp4: 'video',
                avi: 'video',
                flv: 'video',
                f4v: 'video',
                wmv: 'video',
                mov: 'video',
                rmvb: 'video',
                mkv: 'video',
                doc: 'document',
                docx: 'document',
                pdf: 'document',
                ppt: 'ppt',
                pptx: 'ppt',
                mp3: 'audio'
            };

            var paramsDefault = {
                'video' : {videoQuality: 'normal', audioQuality: 'normal'},
                'document' : {},
                'ppt' : {},
                'audio' : {}
            }

            var params = {};
            var extOutput = extOutputs[file.ext];
            if ((this.get('process') == 'auto') && extOutput) {
                params = paramsDefault[extOutput];
                params.output = extOutput;
            } 

            return params;
        },

        _getUploader: function(){
            return this.uploader;
        },

        _initUploaderHook: function() {
            if (hookRegisted) {
                return ;
            } else {
            }

            hookRegisted = true;

            WebUploader.Uploader.register({
                'before-send-file': 'preupload',
                'before-send' : 'checkchunk',
                'after-send-file': 'finishupload',
            }, {
                preupload: function(file) {
                    var deferred = WebUploader.Deferred();

                    file.uploaderWidget._makeFileHash(file).done(function(hash) {
                        file.hash = hash;
                        var params = {
                            fileName: file.name,
                            fileSize: file.size,
                            hash: hash,
                            directives: file.uploaderWidget._getDirectives(file)
                        }

                        $.support.cors = true;

                        var key = 'file_' + file.hash;
                        var value = store.get(key);
                        if(value && value.id) {
                            params.id = value.id;
                        }

                        $.post(file.uploaderWidget.get('initUrl'), params, function(response) {
                            var key = 'file_' + file.hash;
                            if(response.resumed != 'ok') {
                                var value = {};
                                value.id = response.outerId;
                                store.set(key, value);
                            }

                            require.async('./'+response.uploadMode+'-strategy', function(Strategy){
                                var strategy = new Strategy(file, response);
                                file.uploaderWidget.set('strategy', strategy);
                                deferred.resolve();
                            });
                        }, 'json');

                    });

                    return deferred.promise();
                },

                checkchunk: function(block) {
                    var deferred = WebUploader.Deferred();
                    var key = 'file_' + block.file.hash;
                    var resumedChunk = store.get(key);

                    if (resumedChunk === undefined || resumedChunk[block.chunk] === undefined) {
                        block.file.startUploading = true;
                        deferred.resolve();
                    } else {
                        deferred.reject();
                        var strategy = block.file.uploaderWidget.get('strategy');
                        strategy.uploadAccept(block, resumedChunk[block.chunk]);
                    }

                    return deferred.promise();
                },

                finishupload: function(file, ret, hds) {
                    var deferred = WebUploader.Deferred();
                    var key = 'file_' + file.hash;
                    store.remove(key);

                    var strategy = file.uploaderWidget.get('strategy');
                    var data = strategy.finishUpload(deferred);
                    return;
                    data.filename = file.name;
                    data.size = file.size;
                    data.id = file.fileId;

                    $.post(file.uploaderWidget.get('finishUrl'), data, function(response) {
                        deferred.resolve();
                        file.uploaderWidget.trigger('file.uploaded', file, data);

                        file.setStatus('complete');
                        //file.uploaderWidget._getUploader().trigger('uploadSuccess', file, ret, hds);

                        var $li = $('#' + file.id);
                        $li.find('.file-status').html('已上传');
                        $li.find('.file-progress-bar').css('width', '0%');

                    });

                    return deferred.promise();
                }

            });

        },

        _makeFileHash: function(file) {
            var start1 = 0;
            var end1 = (file.size < 4096) ? file.size : 4096;
            var promise1 = this.uploader.md5File(file, start1, end1);

            var start2 = parseInt(file.size / 3);
            var end2 = ((start2 + 4096) > file.size) ? file.size : start2 + 4096;
            var promise2 = this.uploader.md5File(file, start2, end2);

            var start3 = parseInt(file.size / 3 * 2);
            var end3 = ((start3 + 4096) > file.size) ? file.size : start3 + 4096;
            var promise3 = this.uploader.md5File(file, start3, end3);

            var start4 = ((file.size - 4096) < 0) ? 0 : file.size - 4096;
            var end4 = file.size;
            var promise4 = this.uploader.md5File(file, start4, end4);

            var deferred = WebUploader.Deferred();
            WebUploader.when(promise1, promise2, promise3, promise4).done(function(hash1, hash2, hash3, hash4) {
                var hash = hash1.slice(0, 8);
                hash += hash2.slice(8, 16);
                hash += hash3.slice(16, 24);
                hash += hash4.slice(24, 32);
                deferred.resolve('cmd5|' + hash);

            });
            return deferred.promise();
        },

        getFileProcessor: function(file) {
            var extProcessors = {
                'mp4': 'video',
                'avi': 'video',
                'flv': 'video',
                'wmv': 'video',
                'mov': 'video',
                'rmvb': 'video',
                'vob': 'video',
                'mpg': 'video',
                'doc': 'document',
                'docx': 'document',
                'pdf': 'document',
                'ppt': 'document',
                'pptx': 'document'
            };

            var dotPos = file.name.lastIndexOf('.');
            if (dotPos < 0) {
                return '';
            }
            var ext = file.name.slice(dotPos);
            if (!extProcessors[ext]) {
                return '';
            }

            return extProcessors[ext];
        }

    });

    module.exports = BatchUploader;

});