webpackJsonp(["app/js/open-course-manage/picture-crop/index"],[
/* 0 */
/***/ (function(module, exports, __webpack_require__) {

	"use strict";
	
	var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();
	
	var _esImageCrop = __webpack_require__("12695715cd021610570e");
	
	var _esImageCrop2 = _interopRequireDefault(_esImageCrop);
	
	function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { "default": obj }; }
	
	function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }
	
	var CoverCrop = function () {
	  function CoverCrop() {
	    _classCallCheck(this, CoverCrop);
	
	    this.init();
	  }
	
	  _createClass(CoverCrop, [{
	    key: "init",
	    value: function init() {
	      var imageCrop = new _esImageCrop2["default"]({
	        element: "#course-picture-crop",
	        cropedWidth: 480,
	        cropedHeight: 270
	      });
	
	      imageCrop.afterCrop = function (response) {
	        var url = $("#upload-picture-btn").data("url");
	        console.log('afterCrop');
	        $.post(url, { images: response }, function () {
	          console.log($("#upload-picture-btn").data("gotoUrl"));
	          document.location.href = $("#upload-picture-btn").data("gotoUrl");
	        });
	      };
	
	      $("#upload-picture-btn").click(function (event) {
	        $(event.currentTarget).button('loading');
	        event.stopPropagation();
	        imageCrop.crop({
	          imgs: {
	            large: [480, 270],
	            middle: [304, 171],
	            small: [96, 54]
	          }
	        });
	      });
	
	      $('.go-back').click(function () {
	        history.go(-1);
	      });
	    }
	  }]);
	
	  return CoverCrop;
	}();
	
	new CoverCrop();

/***/ })
]);
//# sourceMappingURL=index.js.map