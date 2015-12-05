define(function(require, exports, module) {

	var Class = require('class');

    var LocalStrategy = Class.extend({
    	initialize: function(file) {
            this.file = file;
            var uploaderWidget = file.uploaderWidget;
            uploaderWidget.uploader.option('server', response.uploadUrl + '/chunks');

            var startUrl = uploaderWidget.get('uploadProxyUrl') + '/chunks/start';
            var postData = {file_gid:file.globalId, file_size: file.size, file_name:file.name};

            $.ajax(startUrl, {
                type: 'POST',
                data: postData,
                dataType: 'json',
                headers: {
                    'Upload-Token': uploaderWidget.get('uploadToken')
                },
                success: function() {
                    deferred.resolve();
                }
            });
        },

        uploadBeforeSend: function(object, data, headers){
            var self = this.file.uploaderWidget;
            data.file_gid = object.file.gid;
            data.chunk_number = object.chunk +1;
            headers['Upload-Token'] = self.get('uploadToken');
        },

        finishUpload: function(deferred){
            var file = this.file;
            var xhr = $.ajax(file.uploaderWidget.get('uploadProxyUrl') + '/chunks/finish', {
                type: 'POST',
                data: {file_gid:file.gid},
                dataType: 'json',
                async: false,
                headers: {
                    'Upload-Token': file.uploaderWidget.get('uploadToken')
                }
            });

            var result;
            xhr.done(function( data, textStatus, xhr ) {
                result = data;
            });
            return result;
        },

        uploadAccept: function(object, ret){
            
        }
    });

    module.exports = Cloud2Strategy;
});