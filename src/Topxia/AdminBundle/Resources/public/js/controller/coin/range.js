define(function(require, exports, module) {
	
   var Notify = require('common/bootstrap-notify');
    var Validator = require('bootstrap.validator');
    var EditorFactory = require('common/kindeditor-factory');
    var Uploader = require('upload');
    require('common/validator-rules').inject(Validator);
    require('jquery.select2-css');
    require('jquery.select2');
    require('jquery.bootstrap-datetimepicker');
    require('jquery.form');

exports.run = function() {

        var $form = $("#coin-settings-form"),
        	$modal = $form.children('.coin_content');

        var validator = _initValidator($form, $modal);
        var $editor = _initEditorFields($form, validator);

        var uploader = new Uploader({
            trigger: '#coin-picture-upload',
            name: 'coin_picture',
            action: $('#coin-picture-upload').data('url'),
            data: {'_csrf_token': $('meta[name=csrf-token]').attr('content') },
            accept: 'image/*',
            error: function(file) {
                Notify.danger('上传虚拟币LOGO失败，请重试！')
            },
            success: function(response) {
                response = $.parseJSON(response);
                $("#coin-picture-container").html('<img src="' + response.url + '">');
                $form.find('[name=coin_picture]').val(response.path);
                $("#coin-picture-remove").show();
                Notify.success('上传虚拟币LOGO成功！');
            }
        });

        $("#coin-picture-remove").on('click', function(){
            if (!confirm('确认要删除吗？')) return false;
            var $btn = $(this);
            $.post($btn.data('url'), function(){
                $("#coin-picture-container").html('');
                $form.find('[name=coin_picture]').val('');
                $btn.hide();
                Notify.success('删除虚拟币LOGO成功！');
            }).error(function(){
                Notify.danger('删除虚拟币LOGO失败！');
            });
        });






         function _initValidator($form, $modal)
    {
        var validator = new Validator({
            element: $form,
            autoSubmit: false,
        });

        return validator;
    }

    function _initEditorFields($form, validator)
    {
        
        var editor = EditorFactory.create('#coin_content', 'full', {extraFileUploadParams:{group:'default'}});
        validator.on('formValidate', function(elemetn, event) {
            editor.sync();
        });

        return editor;
    }

		var global_number_reserved = [];
        var validator = new Validator({
            element: '#coin-settings-form'
        });

		$(document).ready(function(){
			var validator_someone = function(i){
				var min = "[name=coin_consume_range_min_"+i+"]";
	    		var pst = "[name=coin_present_"+i+"]";

				validator.addItem({
		            element: min,
		            required: true,
		            rule: 'integer'
		        });
				validator.addItem({
		            element: pst,
		            required: true,
		            rule: 'integer'
		        });	
			};
			
		    var reflash_validation = function(number){
		    	for (var i = 1; i <= number; i++) {
		    		validator_someone(i);		        
		    	};

		    };

		    var reflash_after_delete_range = function(){
				var str=$(this).parent().prev().children('input').attr('id');
				var i = str.charAt(str.length-1);
				var min = "[name=coin_consume_range_min_"+i+"]";
	    		var pst = "[name=coin_present_"+i+"]";
				validator.removeItem(min);
				validator.removeItem(pst);				
				$(this).parent().parent().parent('.range').remove();
				var range_number = parseInt($('#range_number').html())-1;
				$('#range_number').html(range_number);
				global_number_reserved.push(i);		    	
		    };

		    var range_number = parseInt($('#range_number').html());
		    reflash_validation(range_number);
			$('.delete_range').click(reflash_after_delete_range);
			$('.add_range').click(function(){
				var _=document.getElementsByName('coin_template');
				var new_range= _[0].innerHTML;
				var range_number_or_reserved_pop = parseInt($('#range_number').html())+1;
				$('#range_number').html(range_number_or_reserved_pop);
				if (global_number_reserved.length > 0){
					global_number_reserved.sort().reverse();
					range_number_or_reserved_pop = global_number_reserved.pop();
				}
				new_range = new_range.replace(new RegExp('NUM','g'),range_number_or_reserved_pop);
				$('.ranges').append(new_range);
				validator_someone(range_number_or_reserved_pop);
				$('.delete_range'+range_number_or_reserved_pop).click(reflash_after_delete_range);
			});
		});
	};	
});