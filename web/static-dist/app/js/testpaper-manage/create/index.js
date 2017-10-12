webpackJsonp(["app/js/testpaper-manage/create/index"],[
/* 0 */
/***/ (function(module, exports, __webpack_require__) {

	'use strict';
	
	var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();
	
	var _sortable = __webpack_require__("8f840897d9471c8c1fbd");
	
	var _sortable2 = _interopRequireDefault(_sortable);
	
	var _utils = __webpack_require__("9181c6995ae8c5c94b7a");
	
	var _selectLinkage = __webpack_require__("1be2a74362f00ba903a0");
	
	var _selectLinkage2 = _interopRequireDefault(_selectLinkage);
	
	function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { "default": obj }; }
	
	function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }
	
	var TestpaperForm = function () {
	  function TestpaperForm($form) {
	    _classCallCheck(this, TestpaperForm);
	
	    this.$form = $form;
	    this.$description = this.$form.find('[name="description"]');
	    this.validator = null;
	    this.difficultySlider = null;
	    this._initEvent();
	    this._initValidate();
	    this._initSortList();
	    this.scoreSlider = null;
	  }
	
	  _createClass(TestpaperForm, [{
	    key: '_initEvent',
	    value: function _initEvent() {
	      var _this = this;
	
	      this.$form.on('click', '[data-role="submit"]', function (event) {
	        return _this._submit(event);
	      });
	      this.$form.on('click', '[name="mode"]', function (event) {
	        return _this.changeMode(event);
	      });
	      this.$form.on('click', '[name="range"]', function (event) {
	        return _this.changeRange(event);
	      });
	      this.$form.on('blur', '[data-role="count"]', function (event) {
	        return _this.changeCount(event);
	      });
	    }
	  }, {
	    key: 'initScoreSlider',
	    value: function initScoreSlider(passScore, score) {
	      var scoreSlider = document.getElementById('score-slider');
	      var option = {
	        start: passScore,
	        connect: [true, false],
	        tooltips: [true],
	        step: 1,
	        range: {
	          'min': 0,
	          'max': score
	        }
	      };
	      if (this.scoreSlider) {
	        this.scoreSlider.updateOptions(option);
	      } else {
	        this.scoreSlider = noUiSlider.create(scoreSlider, option);
	        scoreSlider.noUiSlider.on('update', function (values, handle) {
	          $('.noUi-tooltip').text((values[handle] / score * 100).toFixed(0) + '%');
	          $('.js-passScore').text(parseInt(values[handle]));
	        });
	      }
	      $('.noUi-handle').attr('data-placement', 'top').attr('data-original-title', Translator.trans('activity.testpaper_manage.pass_score_hint', { 'passScore': passScore })).attr('data-container', 'body');
	      $('.noUi-handle').tooltip({ html: true });
	      $('.noUi-tooltip').text((passScore / score * 100).toFixed(0) + '%');
	    }
	  }, {
	    key: 'changeMode',
	    value: function changeMode(event) {
	      var $this = $(event.currentTarget);
	      if ($this.val() == 'difficulty') {
	        this.$form.find('#difficulty-form-group').removeClass('hidden');
	        this.initDifficultySlider();
	      } else {
	        this.$form.find('#difficulty-form-group').addClass('hidden');
	      }
	    }
	  }, {
	    key: 'changeRange',
	    value: function changeRange(event) {
	      var $this = $(event.currentTarget);
	      $this.val() == 'course' ? this.$form.find('#testpaper-range-selects').addClass('hidden') : this.$form.find('#testpaper-range-selects').removeClass('hidden');
	    }
	  }, {
	    key: 'initDifficultySlider',
	    value: function initDifficultySlider() {
	      if (!this.difficultySlider) {
	        var sliders = document.getElementById('difficulty-percentage-slider');
	        this.difficultySlider = noUiSlider.create(sliders, {
	          start: [30, 70],
	          margin: 30,
	          range: {
	            'min': 0,
	            'max': 100
	          },
	          step: 1,
	          connect: [true, true, true],
	          serialization: {
	            resolution: 1
	          }
	        });
	        sliders.noUiSlider.on('update', function (values, handle) {
	          var simplePercentage = parseInt(values[0]),
	              normalPercentage = values[1] - values[0],
	              difficultyPercentage = 100 - values[1];
	          $('.js-simple-percentage-text').html(Translator.trans('activity.testpaper_manage.simple_percentage', { 'simplePercentage': simplePercentage }));
	          $('.js-normal-percentage-text').html(Translator.trans('activity.testpaper_manage.normal_percentage', { 'normalPercentage': normalPercentage }));
	          $('.js-difficulty-percentage-text').html(Translator.trans('activity.testpaper_manage.difficulty_percentage', { 'difficultyPercentage': difficultyPercentage }));
	          $('input[name="percentages[simple]"]').val(simplePercentage);
	          $('input[name="percentages[normal]"]').val(normalPercentage);
	          $('input[name="percentages[difficulty]"]').val(difficultyPercentage);
	        });
	      }
	    }
	  }, {
	    key: '_initEditor',
	    value: function _initEditor(validator) {
	      var _this2 = this;
	
	      var editor = CKEDITOR.replace(this.$description.attr('id'), {
	        toolbar: 'Simple',
	        filebrowserImageUploadUrl: this.$description.data('imageUploadUrl'),
	        height: 100
	      });
	      editor.on('change', function (a, b, c) {
	        _this2.$description.val((0, _utils.delHtmlTag)(editor.getData()));
	      });
	      editor.on('blur', function () {
	        _this2.$description.val((0, _utils.delHtmlTag)(editor.getData())); //fix ie11
	        validator.form();
	      });
	    }
	  }, {
	    key: 'changeCount',
	    value: function changeCount() {
	      var num = 0;
	      this.$form.find('[data-role="count"]').each(function (index, item) {
	        num += parseInt($(item).val());
	      });
	      this.$form.find('[name="questioncount"]').val(num > 0 ? num : null);
	    }
	  }, {
	    key: '_initValidate',
	    value: function _initValidate() {
	      this.validator = this.$form.validate({
	        rules: {
	          name: {
	            required: true,
	            maxlength: 50,
	            trim: true
	          },
	          description: {
	            required: true,
	            maxlength: 500,
	            trim: true
	          },
	          limitedTime: {
	            min: 0,
	            max: 10000,
	            digits: true
	          },
	          mode: {
	            required: true
	          },
	          range: {
	            required: true
	          },
	          questioncount: {
	            required: true
	          }
	        },
	        messages: {
	          questioncount: Translator.trans('activity.testpaper_manage.question_required_error_hint'),
	          name: {
	            required: Translator.trans('activity.testpaper_manage.input_title_hint'),
	            maxlength: Translator.trans('site.maxlength_hint', { length: 50 })
	          },
	          description: {
	            required: Translator.trans('activity.testpaper_manage.input_description_hint'),
	            maxlength: Translator.trans('site.maxlength_hint', { length: 500 })
	          },
	          mode: Translator.trans('activity.testpaper_manage.generate_mode_hint'),
	          range: Translator.trans('activity.testpaper_manage.question_scope')
	        }
	      });
	      this.$form.find('.testpaper-question-option-item').each(function () {
	        var self = $(this);
	        self.find('[data-role="count"]').rules('add', {
	          min: 0,
	          max: function max() {
	            return parseInt(self.find('[role="questionNum"]').text());
	          },
	          digits: true
	        });
	
	        self.find('[data-role="score"]').rules('add', {
	          min: 0,
	          max: 100,
	          digits: true
	        });
	
	        if (self.find('[data-role="missScore"]').length > 0) {
	          self.find('[data-role="missScore"]').rules('add', {
	            min: 0,
	            max: function max() {
	              return parseInt(self.find('[data-role="score"]').val());
	            },
	            digits: true
	          });
	        }
	      });
	      this._initEditor(this.validator);
	    }
	  }, {
	    key: '_initSortList',
	    value: function _initSortList() {
	      (0, _sortable2["default"])({
	        element: '#testpaper-question-options',
	        itemSelector: '.testpaper-question-option-item',
	        handle: '.question-type-sort-handler',
	        ajax: false
	      });
	    }
	  }, {
	    key: '_submit',
	    value: function _submit(event) {
	      var _this3 = this;
	
	      var $target = $(event.currentTarget);
	      var status = this.validator.form();
	
	      if (status) {
	        $.post($target.data('checkUrl'), this.$form.serialize(), function (result) {
	          if (result.status == 'no') {
	            $('.js-build-check').html(Translator.trans('activity.testpaper_manage.question_num_error'));
	          } else {
	            $('.js-build-check').html('');
	
	            $target.button('loading').addClass('disabled');
	            _this3.$form.submit();
	          }
	        });
	      }
	    }
	  }]);
	
	  return TestpaperForm;
	}();
	
	new TestpaperForm($('#testpaper-form'));
	new _selectLinkage2["default"]($('[name="ranges[courseId]"]'), $('[name="ranges[lessonId]"]'));
	
	$('[name="ranges[courseId]"]').change(function () {
	  var url = $(this).data('checkNumUrl');
	  checkQuestionNum(url);
	});
	
	$('[name="ranges[lessonId]"]').change(function () {
	  var url = $(this).data('checkNumUrl');
	  checkQuestionNum(url);
	});
	
	function checkQuestionNum(url) {
	  var courseId = $('[name="ranges[courseId]"]').val();
	  var lessonId = $('[name="ranges[lessonId]"]').val();
	
	  $.post(url, { courseId: courseId, lessonId: lessonId }, function (data) {
	    $('[role="questionNum"]').text(0);
	
	    $.each(data, function (i, n) {
	      $("[type='" + i + "']").text(n.questionNum);
	    });
	  });
	}

/***/ })
]);
//# sourceMappingURL=index.js.map