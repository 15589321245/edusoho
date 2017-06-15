webpackJsonp(["app/js/course-chapter-manage/index"],[
/* 0 */
/***/ (function(module, exports) {

	import notify from "common/notify";
	
	$('#chapter-title-field').on('keypress', function (e) {
	  if ((e.keyCode || e.which) === 13) {
	    e.preventDefault();
	  }
	});
	
	var sortList = function sortList($list) {
	  var data = $list.sortable("serialize").get();
	
	  $.post($list.data('sortUrl'), { ids: data }, function (response) {
	    var lessonNum = 0;
	    var chapterNum = 0;
	    var unitNum = 0;
	
	    $list.find('.task-manage-unit, .task-manage-chapter').each(function () {
	      var $item = $(this);
	      if ($item.hasClass('item-lesson')) {
	        lessonNum++;
	        $item.find('.number').text(lessonNum);
	      } else if ($item.hasClass('task-manage-unit')) {
	        unitNum++;
	        $item.find('.number').text(unitNum);
	      } else if ($item.hasClass('task-manage-chapter')) {
	        chapterNum++;
	        unitNum = 0;
	        $item.find('.number').text(chapterNum);
	      }
	    });
	  });
	};
	
	$('#course-chapter-btn').on('click', function () {
	  var $this = $(this);
	  var _this = this;
	  var $form = $('#course-chapter-form');
	  var validator = $form.validate({
	    rules: {
	      title: 'required'
	    },
	    ajax: true,
	    currentDom: $this,
	    submitSuccess: function submitSuccess(html) {
	      $this.closest('.modal').modal('hide');
	      if (!$('.js-task-empty').hasClass('hidden')) {
	        $('.js-task-empty').addClass('hidden');
	      }
	      var $item = $('#' + $(html).attr('id'));
	
	      if ($item.length) {
	        $item.replaceWith(html);
	        notify('success', Translator.trans('信息已保存'));
	      } else {
	        var $parent = $('#' + $form.data('parentid'));
	        if ($parent.length) {
	          var add = 0;
	          $parent.nextAll().each(function () {
	            if ($(this).hasClass('task-manage-chapter')) {
	              $(this).before(html);
	              add = 1;
	              return false;
	            }
	          });
	          if (add != 1) {
	            $("#sortable-list").append(html);
	          }
	        } else {
	          $("#sortable-list").append(html);
	        }
	
	        var $list = $("#sortable-list");
	        sortList($list);
	      }
	    }
	  });
	});

/***/ })
]);