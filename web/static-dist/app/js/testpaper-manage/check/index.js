webpackJsonp(["app/js/testpaper-manage/check/index"],[
/* 0 */
/***/ (function(module, exports) {

	var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();
	
	function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }
	
	import QuestionTypeBuilder from '../../testpaper/widget/question-type-builder';
	import { testpaperCardFixed } from 'app/js/testpaper/widget/part';
	
	$.validator.addMethod("score", function (value, element) {
	  var isFloat = /^\d+(\.\d)?$/.test(value);
	  if (!isFloat) {
	    return false;
	  }
	
	  if (Number(value) <= Number($(element).data('score'))) {
	    return true;
	  } else {
	    return false;
	  }
	}, $.validator.format("分数只能是<=题目分数、且>=0的整数或者1位小数"));
	
	var CheckTest = function () {
	  function CheckTest($container) {
	    _classCallCheck(this, CheckTest);
	
	    this.$container = $container;
	    this.checkContent = {};
	    this.$form = $container.find('form');
	    this.$dialog = $container.find('#testpaper-checked-dialog');
	    this.validator = null;
	    this._initEvent();
	    this._init();
	    this._initValidate();
	    testpaperCardFixed();
	  }
	
	  _createClass(CheckTest, [{
	    key: '_initEvent',
	    value: function _initEvent() {
	      var _this = this;
	
	      this.$container.on('focusin', 'textarea', function (event) {
	        return _this._showEssayInputEditor(event);
	      });
	      this.$container.on('click', '[data-role="check-submit"]', function (event) {
	        return _this._submitValidate(event);
	      });
	      this.$container.on('click', '*[data-anchor]', function (event) {
	        return _this._quick2Question(event);
	      });
	      this.$dialog.on('click', '[data-role="finish-check"]', function (event) {
	        return _this._submit(event);
	      });
	      this.$dialog.on('change', 'select', function (event) {
	        return _this._teacherSayFill(event);
	      });
	    }
	  }, {
	    key: '_init',
	    value: function _init() {}
	  }, {
	    key: '_showEssayInputEditor',
	    value: function _showEssayInputEditor(event) {
	      var $shortTextarea = $(event.currentTarget);
	
	      if ($shortTextarea.hasClass('essay-teacher-say-short')) {
	
	        event.preventDefault();
	        event.stopPropagation();
	        $(this).blur();
	        var $longTextarea = $shortTextarea.siblings('.essay-teacher-say-long');
	        var $textareaBtn = $longTextarea.siblings('.essay-teacher-say-btn');
	
	        $shortTextarea.hide();
	        $longTextarea.show();
	        $textareaBtn.show();
	
	        var editor = CKEDITOR.replace($longTextarea.attr('id'), {
	          toolbar: 'Minimal',
	          filebrowserImageUploadUrl: $longTextarea.data('imageUploadUrl')
	        });
	
	        editor.on('blur', function (e) {
	          editor.updateElement();
	          setTimeout(function () {
	            $longTextarea.val(editor.getData());
	            $longTextarea.change();
	          }, 1);
	        });
	
	        editor.on('instanceReady', function (e) {
	          this.focus();
	
	          $textareaBtn.one('click', function () {
	            $shortTextarea.val($(editor.getData()).text());
	            editor.destroy();
	            $longTextarea.hide();
	            $textareaBtn.hide();
	            $shortTextarea.show();
	          });
	        });
	
	        editor.on('key', function () {
	          editor.updateElement();
	          setTimeout(function () {
	            $longTextarea.val(editor.getData());
	            $longTextarea.change();
	          }, 1);
	        });
	
	        editor.on('insertHtml', function (e) {
	          editor.updateElement();
	          setTimeout(function () {
	            $longTextarea.val(editor.getData());
	            $longTextarea.change();
	          }, 1);
	        });
	      }
	    }
	  }, {
	    key: '_initValidate',
	    value: function _initValidate(event) {
	      this.validator = this.$form.validate();
	
	      if ($('*[data-score]:visible').length > 0) {
	        $('*[data-score]:visible').each(function (index) {
	          $(this).rules('add', {
	            required: true,
	            score: true,
	            min: 0,
	            messages: {
	              required: "请输入分数"
	            }
	          });
	        });
	      }
	    }
	  }, {
	    key: '_quick2Question',
	    value: function _quick2Question(event) {
	      var $target = $(event.currentTarget);
	      var position = $($target.data('anchor')).offset();
	      $(document).scrollTop(position.top - 55);
	    }
	  }, {
	    key: '_submitValidate',
	    value: function _submitValidate(event) {
	      var $target = $(event.currentTarget);
	      var scoreTotal = 0;
	
	      if (this.validator == undefined || this.validator.form()) {
	        var self = this;
	        $('*[data-score]').each(function () {
	          var content = {};
	          var questionId = $(this).data('id');
	
	          content['score'] = Number($(this).val());
	          content['teacherSay'] = $('[name="teacherSay_' + questionId + '"]').val();
	
	          self.checkContent[questionId] = content;
	          scoreTotal = scoreTotal + Number($(this).val());
	        });
	
	        var subjectiveScore = Number(this.$dialog.find('[name="objectiveScore"]').val());
	        var totalScore = Number(scoreTotal) + subjectiveScore;
	
	        this.$dialog.find('#totalScore').html(totalScore);
	        this.$dialog.modal('show');
	      }
	    }
	  }, {
	    key: '_submit',
	    value: function _submit(event) {
	
	      var $target = $(event.currentTarget);
	      var teacherSay = this.$dialog.find('textarea').val();
	      var passedStatus = this.$dialog.find('[name="passedStatus"]:checked').val();
	
	      $target.button('loading');
	      $.post($target.data('postUrl'), { result: this.checkContent, teacherSay: teacherSay, passedStatus: passedStatus }, function (response) {
	        window.location.href = $target.data('goto');
	      });
	    }
	  }, {
	    key: '_teacherSayFill',
	    value: function _teacherSayFill(event) {
	      var $target = $(event.currentTarget);
	      var $option = $target.find('option:selected');
	
	      if ($option.val() == '') {
	        this.$dialog.find('textarea').val('');
	      } else {
	        this.$dialog.find('textarea').val($option.text());
	      }
	    }
	  }]);
	
	  return CheckTest;
	}();
	
	new CheckTest($('.container'));

/***/ })
]);