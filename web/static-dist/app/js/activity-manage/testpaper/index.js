webpackJsonp(["app/js/activity-manage/testpaper/index"],{

/***/ 0:
/***/ (function(module, exports, __webpack_require__) {

	'use strict';
	
	var _testpaper = __webpack_require__("8923d040717a9546fc7c");
	
	var _testpaper2 = _interopRequireDefault(_testpaper);
	
	function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { "default": obj }; }
	
	new _testpaper2["default"]($('#iframe-content'));

/***/ }),

/***/ "8923d040717a9546fc7c":
/***/ (function(module, exports, __webpack_require__) {

	'use strict';
	
	Object.defineProperty(exports, "__esModule", {
	  value: true
	});
	
	var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();
	
	var _unit = __webpack_require__("3c398f87808202f19beb");
	
	function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }
	
	var Testpaper = function () {
	  function Testpaper($element) {
	    _classCallCheck(this, Testpaper);
	
	    this.$element = $element;
	    this.$step2_form = this.$element.find('#step2-form');
	    this.$step3_form = this.$element.find('#step3-form');
	    this.$parentiframe = $(window.parent.document).find('#task-create-content-iframe');
	    this.scoreSlider = null;
	    this._init();
	  }
	
	  _createClass(Testpaper, [{
	    key: '_init',
	    value: function _init() {
	      (0, _unit.dateFormat)();
	      this.setValidateRule();
	      this.initEvent();
	      this.initStepForm2();
	    }
	  }, {
	    key: 'initEvent',
	    value: function initEvent() {
	      var _this = this;
	
	      this.$element.find('#testpaper-media').on('change', function (event) {
	        return _this.changeTestpaper(event);
	      });
	      this.$element.find('input[name=doTimes]').on('change', function (event) {
	        return _this.showRedoInterval(event);
	      });
	      this.$element.find('input[name="testMode"]').on('change', function (event) {
	        return _this.startTimeCheck(event);
	      });
	      this.$element.find('input[name="length"]').on('blur', function (event) {
	        return _this.changeEndTime(event);
	      });
	      this.$element.find('#condition-select').on('change', function (event) {
	        return _this.changeCondition(event);
	      });
	      this.initSelectTestpaper(this.$element.find('#testpaper-media').find('option:selected'), $('[name="finishScore"]').val());
	    }
	  }, {
	    key: 'setValidateRule',
	    value: function setValidateRule() {
	      $.validator.addMethod("arithmeticFloat", function (value, element) {
	        return this.optional(element) || /^[0-9]+(\.[0-9]?)?$/.test(value);
	      }, $.validator.format(Translator.trans("activity.testpaper_manage.arithmetic_float_error_hint")));
	
	      $.validator.addMethod("positiveInteger", function (value, element) {
	        return this.optional(element) || /^[1-9]\d*$/.test(value);
	      }, $.validator.format(Translator.trans("activity.testpaper_manage.positive_integer_error_hint")));
	    }
	  }, {
	    key: 'initStepForm2',
	    value: function initStepForm2() {
	      var validator = this.$step2_form.validate({
	        onkeyup: false,
	        rules: {
	          title: {
	            required: true,
	            trim: true,
	            maxlength: 50,
	            course_title: true
	          },
	          mediaId: {
	            required: true,
	            digits: true
	          },
	          length: {
	            required: true,
	            digits: true
	          },
	          startTime: {
	            required: function required() {
	              return $('[name="doTimes"]:checked').val() == 1 && $('[name="testMode"]:checked').val() == 'realTime';
	            },
	            DateAndTime: function DateAndTime() {
	              return $('[name="doTimes"]:checked').val() == 1 && $('[name="testMode"]:checked').val() == 'realTime';
	            }
	          },
	          redoInterval: {
	            required: function required() {
	              return $('[name="doTimes"]:checked').val() == 0;
	            },
	            arithmeticFloat: true,
	            max: 1000000000
	          }
	        },
	        messages: {
	          mediaId: {
	            required: Translator.trans('activity.testpaper_manage.media_error_hint')
	          },
	          redoInterval: {
	            max: Translator.trans("activity.testpaper_manage.max_error_hint")
	          }
	        }
	      });
	      this.$step2_form.data('validator', validator);
	    }
	  }, {
	    key: 'initSelectTestpaper',
	    value: function initSelectTestpaper($option) {
	      var passScore = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : '';
	
	      var mediaId = $option.val();
	      if (mediaId != '') {
	        this.getItemsTable($option.closest('select').data('getTestpaperItems'), mediaId);
	        var score = $option.data('score');
	        if (passScore == '') {
	          passScore = Math.ceil(score * 0.6);
	        }
	        $('#score-single-input').val(passScore);
	        $('.js-score-total').text(score);
	        if (!$('input[name="title"]').val()) {
	          $('input[name="title"]').val($option.text());
	        }
	        this.initScoreSlider(parseInt(passScore), parseInt(score));
	      } else {
	        $('#questionItemShowDiv').hide();
	      }
	    }
	  }, {
	    key: 'changeTestpaper',
	    value: function changeTestpaper(event) {
	      var $target = $(event.currentTarget);
	      var $option = $target.find('option:selected');
	      this.initSelectTestpaper($option);
	    }
	  }, {
	    key: 'showRedoInterval',
	    value: function showRedoInterval(event) {
	      var $this = $(event.currentTarget);
	      if ($this.val() == 1) {
	        $('#lesson-redo-interval-field').closest('.form-group').hide();
	        $('.starttime-check-div').show();
	        this.dateTimePicker();
	      } else {
	        $('#lesson-redo-interval-field').closest('.form-group').show();
	        $('.starttime-check-div').hide();
	      }
	    }
	  }, {
	    key: 'startTimeCheck',
	    value: function startTimeCheck(event) {
	      var $this = $(event.currentTarget);
	
	      if ($this.val() == 'realTime') {
	        $('.starttime-input').removeClass('hidden');
	        this.dateTimePicker();
	      } else {
	        $('.starttime-input').addClass('hidden');
	        //$('input[name="startTime"]').val('0');
	      }
	    }
	  }, {
	    key: 'changeEndTime',
	    value: function changeEndTime(event) {
	      var startTime = $('input[name="startTime"]:visible').val();
	      // if (startTime) {
	      //   this.showEndTime(Date.parse(startTime));
	      // }
	    }
	  }, {
	    key: 'changeCondition',
	    value: function changeCondition(event) {
	      var $this = $(event.currentTarget);
	      var value = $this.find('option:selected').val();
	      value != 'score' ? $('.js-score-form-group').addClass('hidden') : $('.js-score-form-group').removeClass('hidden');
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
	          $('.js-score-tooltip').css('left', (values[handle] / score * 100).toFixed(0) + '%');
	          $('.js-passScore').text(parseInt(values[handle]));
	          $('input[name="finishScore"]').val(parseInt(values[handle]));
	        });
	      }
	      var html = '<div class="score-tooltip js-score-tooltip"><div class="tooltip top" role="tooltip" style="">\n      <div class="tooltip-arrow"></div>\n      <div class="tooltip-inner ">\n\t\t\t' + Translator.trans('activity.testpaper_manage.pass_score_hint', { 'passScore': '<span class="js-passScore">' + passScore + '</span>' }) + '\n      </div>\n      </div></div>';
	      $('.noUi-handle').append(html);
	      $('.noUi-tooltip').text((passScore / score * 100).toFixed(0) + '%');
	      $('.js-score-tooltip').css('left', (passScore / score * 100).toFixed(0) + '%');
	    }
	  }, {
	    key: 'getItemsTable',
	    value: function getItemsTable(url, testpaperId) {
	      $.post(url, { testpaperId: testpaperId }, function (html) {
	        $('#questionItemShowTable').html(html);
	        $('#questionItemShowDiv').show();
	      });
	    }
	  }, {
	    key: 'dateTimePicker',
	    value: function dateTimePicker() {
	      var _this2 = this;
	
	      var data = new Date();
	      var $starttime = $('input[name="startTime"]');
	
	      if ($starttime.is(':visible') && ($starttime.val() == '' || $starttime.val() == '0')) {
	        $starttime.val(data.Format('yyyy-MM-dd hh:mm'));
	      }
	
	      $starttime.datetimepicker({
	        autoclose: true,
	        format: 'yyyy-mm-dd hh:ii',
	        language: document.documentElement.lang,
	        minView: 'hour',
	        endDate: new Date(Date.now() + 86400 * 365 * 10 * 1000)
	      }).on('show', function (event) {
	        _this2.$parentiframe.height($('body').height() + 240);
	      }).on('hide', function (event) {
	        _this2.$step2_form.data('validator').form();
	        _this2.$parentiframe.height($('body').height());
	      }).on('changeDate', function (event) {
	        var date = event.date.valueOf();
	        // this.showEndTime(date);
	      });
	      $starttime.datetimepicker('setStartDate', data);
	      // this.showEndTime(Date.parse($starttime.val()));
	    }
	
	    // showEndTime(date) {
	    //   let limitedTime = $('input[name="limitedTime"]').val();
	    //   if (limitedTime != 0) {
	    //     let endTime = new Date(date + limitedTime * 60 * 1000);
	    //     let endDate = endTime.Format("yyyy-MM-dd hh:mm");
	    //     $('#starttime-show').html(endDate);
	    //     $('.endtime-input').removeClass('hidden');
	    //     $('input[name="endTime"]').val(endDate);
	    //   }else {
	    //     $('.endtime-input').addClass('hidden');
	    //   }
	    // }
	
	  }]);
	
	  return Testpaper;
	}();
	
	exports["default"] = Testpaper;

/***/ })

});
//# sourceMappingURL=index.js.map