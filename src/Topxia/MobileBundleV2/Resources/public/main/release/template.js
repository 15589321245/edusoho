angular.module('app').run(['$templateCache', function($templateCache) {
  'use strict';

  $templateCache.put('/bundles/topxiamobilebundlev2/main/view/alipay_modal.html',
    "<div class=\"alipay-modal modal\"><ion-header-bar class=\"bar-positive\"><a class=\"button button-icon iconfont icon-fanhui\" back=\"close\">关闭</a></ion-header-bar><ion-pane><iframe src=\"{{ payUrl }}\" height=\"100%\" width=\"100%\"></iframe></ion-pane></div>"
  );


  $templateCache.put('/bundles/topxiamobilebundlev2/main/view/category.html',
    "<div class=\"modal category-card\"><ion-content><category-tree data=\"categoryTree\" listener=\"categorySelectedListener\"></category-tree></ion-content></div>"
  );


  $templateCache.put('/bundles/topxiamobilebundlev2/main/view/category_tree.html',
    "<ul class=\"ui-grid-trisect\"><li ng-class=\"'list_' + $index\" ng-repeat=\"categorys in categoryCols\"><ul class=\"ui-list ui-list-text ui-list-cover ui-border-tb\"><li class=\"ui-border-t\" ng-click=\"selectCategory($event, item)\" ng-repeat=\"item in categorys\"><p>{{ item.name }}</p></li></ul></li></ul>"
  );


  $templateCache.put('/bundles/topxiamobilebundlev2/main/view/course.html',
    "<div class=\"ui-bar\" ui-bar><div class=\"ui-bar-bg\"><button class=\"ui-btn btn-outline iconfont icon-fanhui\" back=\"go\"></button><h3 class=\"title\">{{ course.title }}</h3><div class=\"bar-tool\" ng-show=\"member\"><div class=\"ui-pop\" ui-pop><button class=\"ui-btn btn-outline iconfont icon-gengduo ui-pop-btn\"></button><div class=\"ui-pop-bg\" ng-show=\"isShowMenuPop\"></div><ul class=\"ui-pop-content\" ng-show=\"isShowMenuPop\"><li><a class=\"ui-btn btn-outline iconfont icon-xiazai\" ng-click=\"showDownLesson()\">下载</a></li><li><a class=\"ui-btn btn-outline iconfont icon-tixing\" href=\"#/coursenotice/{{ course.id }}\">通知</a></li><li><a class=\"ui-btn btn-outline iconfont icon-shezhi\" ng-click=\"exitLearnCourse()\">退出</a></li></ul></div></div></div></div><div ng-include=\"courseView\"></div>"
  );


  $templateCache.put('/bundles/topxiamobilebundlev2/main/view/course_detail.html',
    "<div class=\"ui-bar\" ui-bar><div class=\"ui-bar-bg\"><button class=\"ui-btn btn-outline iconfont icon-fanhui\" back=\"go\"></button><h3 class=\"title\">课程概览</h3></div></div><div class=\"top-header ui-course ui-course-detail\"><section class=\"ui-panel ui-panel-card ui-border-t\"><h2 class=\"title-body\"><span class=\"title\">课程简介</span></h2><div class=\"ui-panel-content\" ng-bind-html=\"course.about\" ng-img-show></div></section><section class=\"ui-panel ui-panel-card ui-border-t\"><h2 class=\"title-body\"><span class=\"title\">课程目标</span></h2><div class=\"ui-panel-content\"><li ng-repeat=\"goal in course.goals\">{{ goal }}</li></div></section><section class=\"ui-panel ui-panel-card ui-border-t\"><h2 class=\"title-body\"><span class=\"title\">适合人群</span></h2><div class=\"ui-panel-content\"><li ng-repeat=\"audience in course.audiences\">{{ audience }}</li></div></section></div>"
  );


  $templateCache.put('/bundles/topxiamobilebundlev2/main/view/course_learn.html',
    "<div class=\"ui-course top-header\"><div class=\"course-head-body\"><div class=\"course-head\"><img class=\"full-image\" ng-src=\"{{ course.largePicture }}\" img-error=\"course\"><p class=\"mask\"><a class=\"learned\">已完成{{ learnProgress }}</a></p><div class=\"course-progress\"><span ng-style=\"{ width : learnProgress }\"></span></div></div></div><div ng-include=\"  'view/course_lesson.html' | coverIncludePath \"></div></div>"
  );


  $templateCache.put('/bundles/topxiamobilebundlev2/main/view/course_lesson.html',
    "<ul class=\"ui-list ui-list-lesson ui-border-b\" ng-controller=\"CourseLessonController\"><li class=\"{{ lesson.type }}\" ng-repeat=\"lesson in lessons\" ng-class=\"lesson.id == lastLearnStatusIndex ? 'list-green' : '' \" ng-click=\"learnLesson(lesson)\"><div class=\"lesson-status\" ng-if=\"lesson.type !='chapter' && lesson.type !='unit' \"><i class=\"iconfont\" ng-class=\"learnStatuses[lesson.id] == 'finished' ? 'icon-xiazaiwancheng' : 'icon-iconfontround' \"></i></div><div class=\"ui-list-info ui-border-t\" ng-class=\"lesson.type\"><h4 class=\"ui-nowrap\">{{ lesson | formatChapterNumber }} {{ lesson.title }}</h4></div><div class=\"ui-list-action lesson-time\" ng-if=\"lesson.type !='chapter' && lesson.type !='unit' \">{{ lesson.length }}<label class=\"ui-label\">{{ lesson | lessonType }}</label></div></li><list-empty-view class=\"lesson-empty\" data=\"lessons\" title=\"暂无课时\"></list-empty-view><div class=\"ui-loading-wrap\" ng-show=\"loading\"><p>正在加载中...</p><i class=\"ui-loading\"></i></div></ul>"
  );


  $templateCache.put('/bundles/topxiamobilebundlev2/main/view/course_list.html',
    "<div class=\"ui-bar\" ui-bar><div class=\"ui-bar-bg\"><button class=\"ui-btn btn-outline iconfont icon-fanhui\" back=\"go\"></button><h3 class=\"title\">分类搜索</h3></div></div><div class=\"ui-tab tab-top\" ui-tab select=\"empty\"><ul class=\"ui-tab-nav ui-border-b\"><li><span class=\"line\">{{ categoryTab.category }}<i class=\"iconfont icon-keyboardarrowdown\"></i></span></li><li><span class=\"line\">{{ categoryTab.type }}<i class=\"iconfont icon-keyboardarrowdown\"></i></span></li><li><span class=\"line\">{{ categoryTab.sort }}<i class=\"iconfont icon-keyboardarrowdown\"></i></span></li></ul><ul class=\"ui-tab-content tab-display top-tab-header\" style=\"position: fixed\"><li modal><div class=\"content category-card\"><category-tree ng-if=\"categoryTree\" data=\"categoryTree\" listener=\"categorySelectedListener\"></category-tree></div><div class=\"backdrop-bg\"></div></li><li modal><div class=\"content\"><ul class=\"ui-list ui-list-text ui-list-cover ui-list-active\"><li class=\"ui-border-b\" ng-click=\"selectType(item)\" ng-repeat=\"item in courseListTypes\">{{ item.name }}</li></ul></div><div class=\"backdrop-bg\"></div></li><li modal><div class=\"content\"><ul class=\"ui-list ui-list-text ui-list-cover ui-list-active\"><li class=\"ui-border-b\" ng-click=\"selectSort(item)\" ng-repeat=\"item in courseListSorts\">{{ item.name }}</li></ul></div><div class=\"backdrop-bg\"></div></li></ul><div class=\"ui-scroller course-card\" ui-scroll data=\"courses\" on-infinite=\"loadMore()\"><ul class=\"ui-list ui-border-tb\"><li class=\"ui-border-t\" ng-repeat=\"course in courses\" ng-href=\"#/course/{{ course.id }}\"><div class=\"ui-list-img\"><img ng-src=\"{{ course.middlePicture }} \" img-error=\"course\"></div><div class=\"ui-list-info\"><h2 class=\"ui-nowrap ui-list-title\">{{ course.title }}</h2><p class=\"bottom price-body\"><span class=\"origin-price\">{{ course.price | formatPrice }} <s class=\"discount-color\" ng-if=\"course.discountId > 0\">{{ course.originPrice | formatPrice }}</s></span> <span class=\"right\"><i class=\"iconfont icon-person\"></i> {{ course.studentNum }}人在学</span></p></div></li></ul><list-empty-view data=\"courses\" title=\"暂时没有课程\"></list-empty-view><div class=\"ui-loading-wrap\" ng-show=\"canLoad\"><p>正在加载中...</p><i class=\"ui-loading\"></i></div></div></div>"
  );


  $templateCache.put('/bundles/topxiamobilebundlev2/main/view/course_no_learn.html',
    "<div class=\"ui-course top-header\" style=\"padding-bottom: 58px\"><div class=\"course-head-body\"><div class=\"course-head\"><img class=\"full-image\" ng-src=\"{{ course.largePicture }}\" img-error=\"course\"><p class=\"mask\"><a class=\"rating-body\"><span><i class=\"iconfont\" ng-class=\"course.rating > i ? 'icon-favorfill' : 'icon-favor' \" ng-repeat=\"i in ratingArray\"></i></span> <span class=\"rating\">{{ course.rating | limitTo : 3 }}</span></a> <a class=\"right\">{{ course.studentNum }} 人在学</a></p></div><div class=\"item-body\"><p class=\"course-title\">{{ course.title }}</p><p class=\"course-price\"><span class=\"price-block\">{{ course.price | formatPrice }}</span> <span ng-if=\"discount\" class=\"discount-type discount\">{{ discount.type | coverDiscount : course.discount }}</span> <span ng-if=\"discount\" class=\"discount-type type\">{{ discount.name }}</span></p><p ng-if=\"discount\" class=\"discount-body\"><s class=\"price-block\">{{ course.originPrice | formatPrice }}</s> <span>倒计时:{{ discount.endTime | coverDiscountTime }}</span></p></div></div><div class=\"ui-tab tab-top\" ui-tab><ul class=\"ui-tab-nav ui-border-tb\"><li><span class=\"line\">课程课时</span></li><li><span>课时详情</span></li></ul><ul class=\"ui-tab-content tab-display\"><li><div ng-include=\" 'view/course_lesson.html' | coverIncludePath \"></div></li><li><section class=\"ui-panel ui-panel-card ui-border-t\"><h2 class=\"title-body\"><span class=\"title\">课程概览</span> <a class=\"title-tips\" href=\"#/coursedetail/{{ course.id }}\"><i class=\"iconfont icon-gengduo\"></i></a></h2><div class=\"ui-panel-content\" ng-bind-html=\"course.about\" ng-img-show></div></section><section class=\"ui-panel ui-panel-card ui-border-t\"><h2 class=\"title-body\"><span class=\"title\">课程教师 ({{ course.teachers.length }})</span> <a class=\"title-tips\" href=\"#/teacherlist/{{ course.id }}\"><i class=\"iconfont icon-gengduo\"></i></a></h2><div class=\"ui-panel-content\"><ul class=\"ui-list\"><li class=\"ui-border-t\" ng-if=\"course.teachers[0]\"><div class=\"ui-avatar-s\" ui-sref=\"userInfo({ userId : course.teachers[0].id })\"><img ng-src=\"{{ course.teachers[0].avatar | coverAvatar }}\"></div><div class=\"ui-list-info\"><h4 class=\"ui-nowrap\">{{ course.teachers[0].nickname }}<label class=\"ui-label\">教师</label></h4><p class=\"ui-nowrap\">{{ course.teachers[0].title }}</p></div></li><li ng-if=\"! course.teachers[0]\"><p>该课程暂无教师</p></li></ul></div></section><section class=\"ui-panel ui-panel-card ui-border-t\" ng-init=\"loadReviews()\"><h2 class=\"title-body\"><span class=\"title\">课程评价 ({{ reviews.length }})</span> <a class=\"title-tips\" href=\"#/coursereview/{{ course.id }}\"><i class=\"iconfont icon-gengduo\"></i></a></h2><div class=\"ui-panel-content ui-review\"><ul class=\"ui-list\"><li class=\"ui-border-t\" ng-if=\"reviews[0]\"><div class=\"ui-avatar-s\" ui-sref=\"userInfo({ userId : reviews[0].user.id })\"><img ng-src=\"{{ reviews[0].user.mediumAvatar | coverAvatar }}\"></div><div class=\"ui-list-info\"><h4 class=\"ui-nowrap ui-review-header\">{{ reviews[0].user.nickname }} <span class=\"ui-review-rating\"><i class=\"iconfont\" ng-class=\"reviews[0].rating > i ? 'icon-favorfill' : 'icon-favor' \" ng-repeat=\"i in ratingArray\"></i></span></h4><p class=\"ui-nowrap\">{{ reviews[0].content }}</p></div></li><li ng-if=\"! reviews[0]\"><p>该课程暂无评价</p></li></ul></div></section></li></ul></div></div><div class=\"ui-course-tool\" style=\"box-shadow: 0 0 2px rgba(0, 0, 0, 0.5)\" ng-controller=\"CourseToolController\"><button class=\"ui-btn btn-outline btn-col-15 iconfont icon-fenxiang\" ng-click=\"shardCourse()\"></button> <button ng-click=\"favoriteCourse()\" ng-class=\"isFavorited ? 'course-favorited' : '' \" class=\"ui-btn btn-outline btn-col-15 iconfont icon-heart\"></button> <button class=\"ui-btn btn-green\" ng-class=\"course.vipLevelId <= 0 ? 'btn-col-60' : 'btn-col-25' \" ng-click=\"joinCourse()\">立即加入</button> <button class=\"ui-btn btn-yellow btn-col-35 ui-nowrap\" ng-click=\"vipLeand()\" ng-if=\"course.vipLevelId > 0 \">{{ vipLevels[course.vipLevelId - 1].name }}会员免费学</button></div>"
  );


  $templateCache.put('/bundles/topxiamobilebundlev2/main/view/course_notice.html',
    "<div class=\"ui-bar\" ui-bar><div class=\"ui-bar-bg\"><button class=\"ui-btn btn-outline iconfont icon-fanhui\" back=\"go\"></button><h3 class=\"title\">课程消息</h3></div></div><div class=\"top-header ui-course-notice\"><ul class=\"ui-list\"><li class=\"ui-border-b\" ng-repeat=\"notice in notices\"><div class=\"ui-avatar\"><span class=\"notice-icon\"><i class=\"iconfont icon-gonggao\"></i></span></div><div class=\"ui-list-info\"><h4>公告</h4><p class=\"notice-content\" ng-bind-html=\"notice.content\"></p></div></li></ul><list-empty-view data=\"notices\" title=\"暂时没有课程消息\"></list-empty-view><div class=\"ui-tips ui-tips-info\" ng-click=\"loadMore()\" ng-show=\"showLoadMore\"><p>查看更早消息</p></div></div>"
  );


  $templateCache.put('/bundles/topxiamobilebundlev2/main/view/course_pay.html',
    "<div class=\"ui-bar\" ui-bar><div class=\"ui-bar-bg\"><button class=\"ui-btn btn-outline iconfont icon-fanhui\" back=\"go\"></button><h3 class=\"title\">确认订单</h3></div></div><div class=\"top-header ui-course-pay\"><div class=\"ui-form ui-border-t\"><div class=\"ui-form-item ui-border-b ui-list-divider\">确认个人信息</div><div class=\"ui-form-item ui-border-b\"><label>姓名</label><input type=\"text\" placeholder=\"姓名\" ng-model=\"data.userProfile.truename\"> <a class=\"ui-icon-close\" ng-show=\"data.userProfile.truename.length > 0\" ng-click=\"data.userProfile.truename = '' \"></a></div><div class=\"ui-form-item ui-border-b\"><label>手机</label><input type=\"text\" placeholder=\"手机\" ng-model=\"data.userProfile.mobile\"> <a class=\"ui-icon-close\" ng-show=\"data.userProfile.mobile.length > 0\" ng-click=\"data.userProfile.mobile = '' \"></a></div></div><ul class=\"ui-list ui-list-text ui-border-b ui-paycard\"><li class=\"ui-border-t ui-list-divider\">购买课程</li><li class=\"ui-border-t\"><div class=\"ui-list-img\"><img ng-src=\"{{ data.course.middlePicture }}\"></div><div class=\"ui-list-info\"><h4 class=\"ui-nowrap\">{{ data.course.title }}</h4><p class=\"pay-price\">{{ data.course.price | formatPrice }}</p></div></li></ul><ul class=\"ui-list ui-list-link ui-list-text ui-border-b\" ng-if=\"data.isInstalledCoupon\"><li class=\"ui-border-t ui-list-divider\">优惠信息</li><li class=\"ui-border-t\" ng-click=\"selectCoupon()\"><h4 class=\"ui-nowrap\">优惠<span class=\"pay-price\">{{ coupon.decreaseAmount }}</span></h4></li></ul><div class=\"ui-btn-wrap\" style=\"background: #f0f0f0\"><div class=\"pay-body\">需支付:<p class=\"pay-price\">{{ data.course.price - coupon.decreaseAmount | formatPrice }}</p></div><button class=\"ui-btn-lg btn-yellow\" ng-click=\"pay()\">去支付</button></div></div><div class=\"ui-dialog ui-coupon full\"><div class=\"ui-dialog-bg\"><div class=\"ui-dialog-bgcontent\"></div></div><div class=\"ui-dialog-cnt\"><div class=\"ui-bar\" ui-bar><div class=\"ui-bar-bg\"><button class=\"ui-btn btn-outline iconfont icon-fanhui\" back=\"close\"></button><h3 class=\"title\">优惠码</h3><button class=\"ui-btn bar-tool btn-outline small\" ng-click=\"checkCoupon()\">确认</button></div></div><div class=\"ui-form ui-border-t\"><div class=\"ui-form-item ui-border-b ui-list-divider\">填写优惠信息</div><div class=\"ui-form-item ui-border-b\"><label>优惠码</label><input type=\"text\" placeholder=\"优惠码\" ng-model=\"formData.code\"> <a class=\"ui-icon-close\" ng-show=\"formData.code.length > 0\" ng-click=\"formData.code = '' \"></a></div><div class=\"ui-form-item ui-list-divider item-error\">{{ formData.error }}</div></div></div></div>"
  );


  $templateCache.put('/bundles/topxiamobilebundlev2/main/view/course_review.html',
    "<div class=\"ui-bar\" ui-bar><div class=\"ui-bar-bg\"><button class=\"ui-btn btn-outline iconfont icon-fanhui\" back=\"go\"></button><h3 class=\"title\">评价详情</h3></div></div><div class=\"ui-course ui-course-review\"><div class=\"top-header\"><ul class=\"ui-grid-halve ui-border-b\"><li style=\"width:40%\"><div class=\"ui-list-info rating-body\"><h4 class=\"title\">平均评分</h4><h4 class=\"rating-num\">{{ reviewData.info.rating | limitTo : 4 }}</h4><h4><i class=\"iconfont\" ng-class=\"reviewData.info.rating > i ? 'icon-favorfill' : 'icon-favor' \" ng-repeat=\"i in 5 | array\"></i></h4><p>({{ reviewData.info.ratingNum }}评价)</p></div></li><li style=\"width:60%\"><div class=\"ui-list-info\"><div class=\"rating-progress\" ng-repeat=\"i in reviewData.progress track by $index\"><span class=\"progress-title\">{{ $index + 1 }}星</span><div class=\"ui-progress\"><span style=\"width:{{ i | reviewProgress : reviewData.info.rating }}\"></span></div><span class=\"progress\">{{ i | reviewProgress : reviewData.info.rating }}</span></div></div></li></ul></div><div class=\"ui-scroller\" ui-scroll data=\"reviews\" on-infinite=\"loadMore()\"><div class=\"ui-panel-content ui-review\"><ul class=\"ui-list\" style=\"padding : 16px\"><li class=\"ui-border-t\" ng-repeat=\"review in reviews\"><div class=\"ui-avatar-s\" ui-sref=\"userInfo({ userId : review.user.id })\"><img ng-src=\"{{ review.user.mediumAvatar | coverAvatar }}\" img-error=\"avatar\"></div><div class=\"ui-list-info\"><h4 class=\"ui-nowrap ui-review-header\">{{ review.user.nickname }} <span class=\"ui-review-rating\"><i class=\"iconfont\" ng-class=\"review.rating > i ? 'icon-favorfill' : 'icon-favor' \" ng-repeat=\"i in 5 | array\"></i></span></h4><p class=\"ui-nowrap\">{{ review.content }}</p></div></li></ul></div><list-empty-view data=\"reviews\" title=\"暂时没有评价\"></list-empty-view><div class=\"ui-loading-wrap\" ng-show=\"canLoad\"><p>正在加载中...</p><i class=\"ui-loading\"></i></div></div></div>"
  );


  $templateCache.put('/bundles/topxiamobilebundlev2/main/view/course_setting.html',
    "<div class=\"ui-bar\" ui-bar><div class=\"ui-bar-bg\"><button class=\"ui-btn btn-outline iconfont icon-fanhui\" back=\"go\"></button><h3 class=\"title\">课程设置</h3></div></div><div class=\"top-header\"><div class=\"ui-form ui-border-t\"><div class=\"ui-form-item ui-form-item-switch ui-border-b\"><p>讨论组消息免打扰</p><label class=\"ui-switch\"><input type=\"checkbox\"></label></div><div class=\"ui-form-item ui-form-item-switch ui-border-b\"><p>课程消息免打扰</p><label class=\"ui-switch\"><input type=\"checkbox\"></label></div><div class=\"ui-form-item ui-form-item-switch\"><div ng-if=\"isLearn\" class=\"ui-btn-wrap sign-padding\"><button class=\"ui-btn-lg btn-red\" ng-click=\"exitLearnCourse()\">退出课程</button></div></div></div></div>"
  );


  $templateCache.put('/bundles/topxiamobilebundlev2/main/view/courselist_modal.html',
    "<div class=\"modal courselist-modal\"><ion-tabs class=\"tabs-green tabs-top tabs-background-positive tabs-color-light\"><div class=\"splice-line\"></div><ion-tab title=\"全部分类\"><ion-nav-view name=\"courselist-type\"><ion-view><ion-content class=\"courselist-content\"><div class=\"list\"><a class=\"item\" ng-click=\"selectType(item.type)\" ng-repeat=\"item in courseListTypes\">{{ item.name }}</a></div></ion-content></ion-view></ion-nav-view></ion-tab><ion-tab title=\"综合排序\"><ion-nav-view name=\"courselist-sort\"><ion-view><ion-content class=\"courselist-content\"><div class=\"list\"><a class=\"item\" ng-click=\"selectSort(item.type)\" ng-repeat=\"item in courseListSorts\">{{ item.name }}</a></div></ion-content></ion-view></ion-nav-view></ion-tab></ion-tabs></div>"
  );


  $templateCache.put('/bundles/topxiamobilebundlev2/main/view/courselist_sort.html',
    "<ion-view><ion-content class=\"padding\"><div class=\"list\"><a class=\"item\" ng-click=\"selectCategory(item)\" ng-repeat=\"item in courseListSorts\">{{ item }}</a></div></ion-content></ion-view>"
  );


  $templateCache.put('/bundles/topxiamobilebundlev2/main/view/courselist_type.html',
    "<ion-view><ion-content class=\"padding\"><div class=\"list\"><a class=\"item\" ng-click=\"selectCategory(item)\" ng-repeat=\"item in courseListTypes\">{{ item }}</a></div></ion-content></ion-view>"
  );


  $templateCache.put('/bundles/topxiamobilebundlev2/main/view/found.html',
    "<div class=\"ui-bar\" ui-bar><div class=\"ui-bar-bg\"><button class=\"ui-btn btn-outline iconfont icon-menu\" ng-click=\"toggle()\"></button><h3 class=\"title\" ng-click=\"openModal()\">发现<i class=\"iconfont icon-arrowdropdown\"></i></h3><button class=\"ui-btn bar-tool btn-outline iconfont icon-sousuo\"></button></div></div><div class=\"ui-tab tab-top\" ui-tab><ul class=\"ui-tab-nav ui-border-b\"><li><span class=\"line\">课程</span></li><li><span>直播</span></li></ul><ul class=\"ui-tab-content tab-display\"><li><div ui-view=\"found-course\"></div></li><li><div ui-view=\"found-live\"></div></li></ul></div><div class=\"ui-dialog full\"><div class=\"ui-dialog-bg\"><div class=\"ui-dialog-bgcontent\"></div></div><div class=\"ui-dialog-cnt\"><div class=\"ui-dialog-bd category-card\"><category-tree data=\"categoryTree\" listener=\"categorySelectedListener\"></category-tree></div></div></div>"
  );


  $templateCache.put('/bundles/topxiamobilebundlev2/main/view/found_classroom.html',
    "<ion-view><ion-content class=\"found-body content-padding\"><ion-slide-box on-slide-changed=\"slideHasChanged($index)\"><ion-slide ng-repeat=\"banner in banners\" slide-init><div class=\"box banner\"><img ng-src=\"{{ banner.url }}\"></div></ion-slide></ion-slide-box><div class=\"course-card\"><h4>推荐</h4><div class=\"list\"><a class=\"item item-thumbnail-left\" href=\"#\" ng-repeat=\"classRoom in classRooms\"><img src=\"{{ classRoom.middlePicture | coverPic}}\"><h2>{{ classRoom.title }}</h2><p class=\"bottom price-body\"><span class=\"origin-price\">{{ classRoom.price | formatPrice }} <s class=\"discount-color\" ng-if=\"recommendCourse.discountId > 0\">{{ classRoom.originPrice | formatPrice }}</s></span> <span class=\"right\"><i class=\"iconfont icon-person\"></i> {{ classRoom.studentNum }}人在学</span></p></a></div><a class=\"button button-clear more\" href=\"#/courselist\">查看更多</a></div></ion-content></ion-view>"
  );


  $templateCache.put('/bundles/topxiamobilebundlev2/main/view/found_course.html',
    "<div class=\"top-tab-header ui-found-course\" ng-controller=\"FoundCourseController\"><div class=\"ui-slider course-slider\" slider=\"course-slider\" ui-slider-box=\"banners\"><ul class=\"ui-slider-content\"><li ng-repeat=\"banner in banners\" ng-click=\"bannerClick(banner)\"><span style=\"background-image:url({{ banner.url }})\"></span></li></ul></div><div class=\"course-card\"><h4>推荐</h4><ul class=\"ui-list ui-border-tb\"><li class=\"ui-border-t\" ng-repeat=\"recommendCourse in recommedCourses\" ng-href=\"#/course/{{ recommendCourse.id  }}\"><div class=\"ui-list-img\"><span style=\"background-image:url( {{ recommendCourse.middlePicture }} )\"></span></div><div class=\"ui-list-info\"><h2 class=\"ui-nowrap ui-list-title\">{{ recommendCourse.title }}</h2><p class=\"bottom price-body\"><span class=\"origin-price\">{{ recommendCourse.price | formatPrice }} <s class=\"discount-color\" ng-if=\"recommendCourse.discountId > 0\">{{ recommendCourse.originPrice | formatPrice }}</s></span> <span class=\"right\"><i class=\"iconfont icon-person\"></i> {{ recommendCourse.studentNum }}人在学</span></p></div></li></ul><div class=\"ui-btn-group\" ng-if=\"recommedCourses.length > 0\"><button type=\"button\" class=\"btn-green\" ng-href=\"#/courselist/\">更多</button></div></div><div class=\"course-card\"><h4>最新</h4><list-empty-view data=\"latestCourses\" title=\"暂时没有最新课程\"></list-empty-view><ul class=\"ui-list ui-border-tb\"><li class=\"ui-border-t\" ng-repeat=\"latestCourse in latestCourses\" ng-href=\"#/course/{{ latestCourse.id  }}\"><div class=\"ui-list-img\"><span style=\"background-image:url( {{ latestCourse.middlePicture }} )\"></span></div><div class=\"ui-list-info\"><h2 class=\"ui-nowrap ui-list-title\">{{ latestCourse.title }}</h2><p class=\"bottom price-body\"><span class=\"origin-price\">{{ latestCourse.price | formatPrice }} <s class=\"discount-color\" ng-if=\"latestCourse.discountId > 0\">{{ latestCourse.originPrice | formatPrice }}</s></span> <span class=\"right\"><i class=\"iconfont icon-person\"></i> {{ latestCourse.studentNum }}人在学</span></p></div></li></ul><div class=\"ui-btn-group\" ng-if=\"latestCourses.length > 0\"><button type=\"button\" class=\"btn-green\" ng-href=\"#/courselist/\">更多</button></div></div></div>"
  );


  $templateCache.put('/bundles/topxiamobilebundlev2/main/view/found_live.html',
    "<div class=\"top-tab-header ui-found-course\" ng-controller=\"FoundLiveController\"><div class=\"course-card\"><h4>推荐</h4><list-empty-view data=\"liveRecommedCourses\" title=\"暂时没有推荐课程\"></list-empty-view><ul class=\"ui-list ui-border-tb\"><li class=\"ui-border-t\" ng-repeat=\"liveRecommedCourse in liveRecommedCourses\" ng-href=\"#/course/{{ liveRecommedCourse.id  }}\"><div class=\"ui-list-img\"><span style=\"background-image:url( {{ liveRecommedCourse.middlePicture }} )\"></span></div><div class=\"ui-list-info\"><h2 class=\"ui-nowrap ui-list-title\">{{ liveRecommedCourse.title }}</h2><p class=\"bottom price-body\"><span class=\"origin-price\">{{ liveRecommedCourse.price | formatPrice }} <s class=\"discount-color\" ng-if=\"liveRecommedCourse.discountId > 0\">{{ liveRecommedCourse.originPrice | formatPrice }}</s></span> <span class=\"right\"><i class=\"iconfont icon-person\"></i> {{ liveRecommedCourse.studentNum }}人在学</span></p></div></li></ul><div class=\"ui-btn-group\" ng-if=\"liveRecommedCourses.length > 0\"><button type=\"button\" class=\"btn-green\" ng-href=\"#/courselist/\">更多</button></div></div><div class=\"course-card\"><h4>最新</h4><list-empty-view data=\"liveLatestCourses\" title=\"暂时没有最新课程\"></list-empty-view><ul class=\"ui-list ui-border-tb\"><li class=\"ui-border-t\" ng-repeat=\"liveLatestCourse in liveLatestCourses\" ng-href=\"#/course/{{ liveLatestCourse.id  }}\"><div class=\"ui-list-img\"><span style=\"background-image:url( {{ liveLatestCourse.middlePicture }} )\"></span></div><div class=\"ui-list-info\"><h2 class=\"ui-nowrap ui-list-title\">{{ liveLatestCourse.title }}</h2><p class=\"bottom price-body\"><span class=\"origin-price\">{{ liveLatestCourse.price | formatPrice }} <s class=\"discount-color\" ng-if=\"liveLatestCourse.discountId > 0\">{{ liveLatestCourse.originPrice | formatPrice }}</s></span> <span class=\"right\"><i class=\"iconfont icon-person\"></i> {{ liveLatestCourse.studentNum }}人在学</span></p></div></li></ul><div class=\"ui-btn-group\" ng-if=\"liveLatestCourses.length > 0\"><button type=\"button\" class=\"btn-green\" ng-href=\"#/courselist/\">更多</button></div></div></div>"
  );


  $templateCache.put('/bundles/topxiamobilebundlev2/main/view/lesson.html',
    "<div class=\"ui-bar\" ui-bar><div class=\"ui-bar-bg\"><button class=\"ui-btn btn-outline iconfont icon-fanhui\" back=\"go\"></button><h3 class=\"title\">{{ lesson.title }}</h3></div></div><div class=\"top-header ui-lesson\"><div ng-include=\"lessonView\"></div></div><script type=\"text/ng-template\" id=\"view/lesson_text.html\"><div class=\"lesson-content\" ng-html=\"lesson.content\" ng-img-show></div></script>"
  );


  $templateCache.put('/bundles/topxiamobilebundlev2/main/view/login.html',
    "<div class=\"ui-bar\" ui-bar><div class=\"ui-bar-bg\"><button class=\"ui-btn btn-outline iconfont icon-fanhui\" back=\"go\"></button><h3 class=\"title\">登 录</h3><a class=\"ui-btn btn-outline bar-tool small\" href=\"#/regist\">注册</a></div></div><div class=\"top-header sign login\"><div class=\"ui-form\"><form action=\"#\"><div class=\"ui-form-item ui-form-item-pure ui-border-b\"><input type=\"text\" placeholder=\"手机号/邮箱/昵称\" ng-model=\"user.username\"> <a class=\"ui-icon-close\" ng-show=\"user.username.length > 0\" ng-click=\"user.username = '' \"></a></div><div class=\"ui-form-item ui-form-item-pure ui-border-b margin-t\"><input type=\"password\" placeholder=\"密码\" ng-model=\"user.password\"> <a class=\"ui-icon-close\" ng-show=\"user.password.length > 0\" ng-click=\"user.password = '' \"></a></div></form><div class=\"ui-btn-wrap padding-none margin-t\"><button class=\"ui-btn-lg btn-green\" ng-click=\"login(user)\">登 录</button></div></div><div style=\"margin-top:16px;text-align: center\"><div class=\"shard-label\"><hr><span>其他方式登录</span></div><ul class=\"ui-grid-trisect\"><li><i class=\"iconfont icon-weixin shard-login weixin-login\"></i></li><li><i class=\"iconfont icon-qq shard-login qq-login\"></i></li><li><i class=\"iconfont icon-icon shard-login weibo-login\"></i></li></ul></div></div>"
  );


  $templateCache.put('/bundles/topxiamobilebundlev2/main/view/main.html',
    "<div side-view class=\"side-content\"><div id=\"st-container\" class=\"st-container\"><nav class=\"st-menu st-effect-1 menu-content\" id=\"menu-1\"><ul class=\"ui-list ui-list-text ui-border-tb ui-list-avatar\"><li class=\"ui-border-t\" ng-click=\"showMyView('userinfo')\"><div class=\"ui-avatar-one\"><img ng-src=\"{{ user.mediumAvatar | coverAvatar }}\"></div><div class=\"ui-list-info ui-border-t\" ng-if=\"user\"><h3>{{ user.nickname }}</h3><p>{{ user.signature }}</p></div><div class=\"ui-list-info ui-border-t\" ng-if=\"!user\"><h3>个人中心</h3><p>登录/注册</p></div></li></ul><div ng-if=\"! user\"><div class=\"ui-btn-wrap\"><a class=\"ui-btn-lg btn-green\" ng-click=\"showMyView('login')\">登 录</a></div><div class=\"ui-btn-wrap\"><a class=\"ui-btn-lg btn-black\" href=\"#/regist\">注 册</a></div></div><ul class=\"ui-list ui-list-text ui-border-tb ui-list-active\" ng-if=\"user\"><li class=\"ui-border-t\" ng-click=\"showMyView('mylearn')\"><i class=\"icon iconfont icon-xuexishuben\"></i> 我的学习</li><li class=\"ui-border-t\" ui-sref=\"viplist()\"><i class=\"icon iconfont icon-infenicon14\"></i> 开通会员</li><li class=\"ui-border-t\" ng-click=\"showMyView('myfavorite')\"><i class=\"icon iconfont icon-shoucang\"></i> 我的收藏</li><li class=\"ui-border-t\" ng-click=\"showMyView('mygroup')\"><i class=\"icon iconfont icon-fabiao\"></i> 我的发表</li><li class=\"ui-border-t\" ng-click=\"showMyView('setting')\"><i class=\"icon iconfont icon-shezhi\"></i> 设置</li></ul></nav><div class=\"st-content\"><div class=\"st-pusher\"></div><div ui-view=\"menuContent\"></div></div></div></div>"
  );


  $templateCache.put('/bundles/topxiamobilebundlev2/main/view/main_content.html',
    "<div class=\"ui-bar\" ui-bar><div class=\"ui-bar-bg\"><button class=\"ui-btn btn-outline iconfont icon-menu\" ng-click=\"toggle()\"></button><h3 class=\"title\" ng-click=\"openModal()\">发现<i class=\"iconfont icon-arrowdropdown\"></i></h3><button class=\"ui-btn bar-tool btn-outline iconfont icon-sousuo\" ng-href=\"#/search\"></button></div></div><div class=\"ui-tab tab-top\" ui-tab><ul class=\"ui-tab-nav ui-border-b\"><li><span class=\"line\">课程</span></li><li><span>直播</span></li></ul><ul class=\"ui-tab-content tab-display\"><li><div ng-include=\" 'view/found_course.html' | coverIncludePath \"></div></li><li><div ng-include=\" 'view/found_live.html' | coverIncludePath \"></div></li></ul></div><div class=\"ui-dialog full\"><div class=\"ui-dialog-bg\"><div class=\"ui-dialog-bgcontent\"></div></div><div class=\"ui-dialog-cnt\"><div class=\"ui-dialog-bd category-card\"><category-tree data=\"categoryTree\" listener=\"categorySelectedListener\"></category-tree></div></div></div>"
  );


  $templateCache.put('/bundles/topxiamobilebundlev2/main/view/menu_view.html',
    "<ion-side-menus><ion-side-menu-content><ion-nav-view name=\"menuContent\"></ion-nav-view></ion-side-menu-content><ion-side-menu side=\"left\"><ion-content><ul class=\"list\"><a class=\"item item-avatar\" ng-click=\"showUserInfo()\"><img ng-src=\"{{ user.mediumAvatar | coverAvatar }}\"><h3>{{ user.nickname }}</h3><p>{{ user.nickname }}</p></a> <a class=\"item item-icon-left\" menu-toggle=\"left\" ng-click=\"showMyLearn()\"><i class=\"icon iconfont icon-school\"></i> 我的学习</a> <a class=\"item item-icon-left\" menu-toggle=\"left\" ng-click=\"showMyFavorite()\"><i class=\"icon iconfont icon-search\"></i> 我的收藏</a> <a class=\"item item-icon-left\" menu-toggle=\"left\" ng-click=\"showMyGroup()\"><i class=\"icon iconfont icon-send\"></i> 我的发表</a> <a class=\"item item-icon-left\" hmenu-toggle=\"left\"><i class=\"icon iconfont icon-setting\"></i> 设置</a></ul></ion-content></ion-side-menu></ion-side-menus>"
  );


  $templateCache.put('/bundles/topxiamobilebundlev2/main/view/myfavorite.html',
    "<div class=\"ui-bar\" ui-bar><div class=\"ui-bar-bg\"><button class=\"ui-btn btn-outline iconfont icon-fanhui\" back=\"go\"></button><h3 class=\"title\">我的收藏</h3></div></div><div class=\"ui-tab tab-top\" ui-tab><ul class=\"ui-tab-nav ui-border-b\"><li><span class=\"line\">课程</span></li><li><span>直播课</span></li></ul><ul class=\"ui-tab-content tab-display ui-course-learn\"><li><div ng-include=\" 'view/myfavorite_course.html' | coverIncludePath \"></div></li><li><div ng-include=\" 'view/myfavorite_live.html' | coverIncludePath \"></div></li></ul></div>"
  );


  $templateCache.put('/bundles/topxiamobilebundlev2/main/view/myfavorite_course.html',
    "<div class=\"ui-scroller course-card\" ng-controller=\"MyFavoriteCourseController\"><ul class=\"ui-list ui-border-tb\"><li class=\"ui-list-avatar\" ng-repeat=\"course in data.course.data\" ui-sref=\"course({ courseId : course.id })\"><div class=\"ui-list-img\"><img ng-src=\"{{ course.middlePicture | coverPic }}\" img-error=\"course\"></div><div class=\"ui-list-info ui-border-t\"><h2 class=\"ui-nowrap ui-list-title\">{{ course.title }}</h2><p class=\"bottom price-body\"><span class=\"origin-price\">{{ course.price | formatPrice }} <s class=\"discount-color\" ng-if=\"course.discountId > 0\">{{ course.originPrice | formatPrice }}</s></span></p></div></li></ul><list-empty-view data=\"data.course.data\" title=\"暂时没有收藏课程\"></list-empty-view></div>"
  );


  $templateCache.put('/bundles/topxiamobilebundlev2/main/view/myfavorite_live.html',
    "<div class=\"ui-scroller course-card\" ng-controller=\"MyFavoriteLiveController\"><ul class=\"ui-list ui-border-tb\"><li class=\"ui-border-t ui-list-avatar\" ng-repeat=\"course in data.live.data\" ui-sref=\"course({ courseId : course.id })\"><div class=\"ui-list-img\"><img ng-src=\"{{ course.middlePicture | coverPic }}\" img-error=\"course\"></div><div class=\"ui-list-info\"><h2 class=\"ui-nowrap ui-list-title\">{{ course.title }}</h2><p class=\"bottom price-body\"><span class=\"origin-price\">{{ course.price | formatPrice }} <s class=\"discount-color\" ng-if=\"course.discountId > 0\">{{ course.originPrice | formatPrice }}</s></span></p></div></li></ul><list-empty-view data=\"data.live.data\" title=\"暂时没有收藏直播课程\"></list-empty-view></div>"
  );


  $templateCache.put('/bundles/topxiamobilebundlev2/main/view/mygroup.html',
    "<div class=\"ui-bar\" ui-bar><div class=\"ui-bar-bg\"><button class=\"ui-btn btn-outline iconfont icon-fanhui\" back=\"go\"></button><h3 class=\"title\">我发表的</h3></div></div><div class=\"ui-tab tab-top\" ui-tab><ul class=\"ui-tab-nav ui-border-b\"><li><span class=\"line\">问答</span></li><li><span class=\"line\">笔记</span></li><li><span>讨论</span></li></ul><ul class=\"ui-tab-content tab-display ui-course-learn\"><li><div ng-include=\" 'view/mygroup_question.html' | coverIncludePath \"></div></li><li><div ng-include=\" 'view/mygroup_note.html' | coverIncludePath \"></div></li><li><div ng-include=\" 'view/mygroup_thread.html' | coverIncludePath \"></div></li></ul></div>"
  );


  $templateCache.put('/bundles/topxiamobilebundlev2/main/view/mygroup_note.html',
    "<div ng-controller=\"MyGroupNoteController\"><div class=\"ui-scroller\" ui-scroll data=\"data\" on-infinite=\"loadMore()\"><ul class=\"ui-list ui-list-card ui-question\"><li class=\"ui-border-t\" ng-repeat=\"note in data\" ui-sref=\"note({ noteId : note.id })\"><div class=\"ui-list-info\"><h4 class=\"padding\">{{ note.title }}</h4><p class=\"padding\">{{ note.content | blockStr : 50 }}</p><ul class=\"ui-grid-halve padding\"><li class=\"ui-nowrap\">{{ note.lessonTitle }}</li><li>{{ question.updatedTime | coverLearnTime }}</li></ul></div></li></ul><list-empty-view data=\"data\" title=\"暂时没有笔记\"></list-empty-view><div class=\"ui-loading-wrap\" ng-show=\"canLoad\"><p>正在加载中...</p><i class=\"ui-loading\"></i></div></div></div>"
  );


  $templateCache.put('/bundles/topxiamobilebundlev2/main/view/mygroup_question.html',
    "<div ng-controller=\"MyGroupQuestionController\"><div class=\"ui-scroller\" ui-scroll data=\"courses\" on-infinite=\"loadMore()\"><ul class=\"ui-list ui-list-card ui-question\"><li class=\"ui-border-t\" ng-repeat=\"question in data\" ui-sref=\"question({ courseId : question.courseId, threadId : question.id })\"><div class=\"ui-list-info\"><h4 class=\"padding\">{{ question.title }}</h4><p class=\"padding\">{{ question.latestPostContent | blockStr : 50 }}</p><ul class=\"ui-grid-trisect padding\"><li class=\"ui-nowrap\">{{ question.courseTitle }}</li><li>{{ question.latestPostTime | coverLearnTime }}</li><li>{{ question.postNum }}个回复</li></ul></div></li></ul><list-empty-view data=\"courses\" title=\"暂时没有课程\"></list-empty-view><div class=\"ui-loading-wrap\" ng-show=\"canLoad\"><p>正在加载中...</p><i class=\"ui-loading\"></i></div></div></div>"
  );


  $templateCache.put('/bundles/topxiamobilebundlev2/main/view/mygroup_thread.html',
    "<div ng-controller=\"MyGroupThreadController\"><div class=\"ui-scroller\" ui-scroll data=\"data\" on-infinite=\"loadMore()\"><ul class=\"ui-list ui-list-card ui-question\"><li class=\"ui-border-t\" ng-repeat=\"question in data\" ui-sref=\"question({ courseId : question.courseId, threadId : question.id })\"><div class=\"ui-list-info\"><h4 class=\"padding\">{{ question.title }}</h4><p class=\"padding\">{{ question.latestPostContent | blockStr : 50 }}</p><ul class=\"ui-grid-trisect padding\"><li class=\"ui-nowrap\">{{ question.courseTitle }}</li><li>{{ question.latestPostTime | coverLearnTime }}</li><li>{{ question.postNum }}个回复</li></ul></div></li></ul><list-empty-view data=\"data\" title=\"暂时没有课程\"></list-empty-view><div class=\"ui-loading-wrap\" ng-show=\"canLoad\"><p>正在加载中...</p><i class=\"ui-loading\"></i></div></div></div>"
  );


  $templateCache.put('/bundles/topxiamobilebundlev2/main/view/myinfo.html',
    "<div class=\"ui-bar\" ui-bar><div class=\"ui-bar-bg\"><button class=\"ui-btn btn-outline iconfont icon-fanhui\" back=\"go\"></button><h3 class=\"title\">用户信息</h3></div></div><div class=\"top-header ui-userinfoinfo\"><ul class=\"ui-list ui-list-text ui-border-tb\"><li class=\"ui-border-t\"><div class=\"ui-list-info\"><h4>头像</h4></div><div class=\"ui-avatar\"><img class=\"avatar\" ng-src=\"{{ userinfo.mediumAvatar | coverAvatar }}\"></div></li><li class=\"ui-border-t\"><div class=\"ui-list-info\"><h4>昵称</h4></div><div class=\"ui-list-action\">{{ userinfo.nickname }}</div></li><li class=\"ui-border-t\"><div class=\"ui-list-info\"><h4>性别</h4></div><div class=\"ui-list-action\">{{ userinfo.gender | coverGender }}</div></li><li class=\"ui-border-t\"><div class=\"ui-list-info\"><h4>个性签名</h4></div><div class=\"ui-list-action ui-nowrap-multi\">{{ userinfo.signature }}</div></li></ul></div>"
  );


  $templateCache.put('/bundles/topxiamobilebundlev2/main/view/mylearn.html',
    "<div class=\"ui-bar\" ui-bar><div class=\"ui-bar-bg\"><button class=\"ui-btn btn-outline iconfont icon-fanhui\" back=\"go\"></button><h3 class=\"title\">我的学习</h3></div></div><div class=\"ui-tab tab-top\" ui-tab><ul class=\"ui-tab-nav ui-border-b\"><li><span class=\"line\">课程</span></li><li><span>直播课</span></li></ul><ul class=\"ui-tab-content tab-display ui-course-learn\"><li><div class=\"ui-scroller\" ui-scroll data=\"course.data\" on-infinite=\"loadMore()\"><ul class=\"ui-list ui-border-tb\"><li class=\"ui-border-t ui-list-avatar\" ng-repeat=\"course in course.data\" ui-sref=\"course({ courseId : course.id })\"><div class=\"ui-list-img\"><img ng-src=\"{{ course.middlePicture | coverPic }}\" img-error=\"course\"></div><div class=\"ui-list-info\"><h4 class=\"ui-nowrap ui-list-title\">{{ course.title }}</h4><p><span>上次学习时间:{{ course.startTime | coverLearnTime }}</span><div class=\"ui-progress\"><span ng-style=\"course | coverLearnProsser\"></span></div></p></div></li></ul><list-empty-view data=\"course.data\" title=\"暂时没有课程\"></list-empty-view><div class=\"ui-loading-wrap\" ng-show=\"canLoadMore('course')\"><p>正在加载中...</p><i class=\"ui-loading\"></i></div></div></li><li><div class=\"ui-scroller\" ui-scroll data=\"live.data\" on-infinite=\"loadMore()\"><ul class=\"ui-list ui-border-tb\"><li class=\"ui-border-t ui-list-avatar\" ng-repeat=\"course in live.data\" ui-sref=\"course({ courseId : course.id })\"><div class=\"ui-list-img\"><img ng-src=\"{{ course.middlePicture | coverPic }}\" img-error=\"course\"></div><div class=\"ui-list-info\"><h4 class=\"ui-nowrap ui-list-title\">{{ course.title }}</h4><p><span>上次学习时间:{{ course.startTime | coverLearnTime }}</span><div class=\"ui-progress\"><span ng-style=\"course | coverLearnProsser\"></span></div></p></div></li></ul><list-empty-view data=\"live.data\" title=\"暂时没有直播课程\"></list-empty-view><div class=\"ui-loading-wrap\" ng-show=\"canLoadMore('live')\"><p>正在加载中...</p><i class=\"ui-loading\"></i></div></div></li></ul></div>"
  );


  $templateCache.put('/bundles/topxiamobilebundlev2/main/view/mylearn_classroom.html',
    "<ion-view><ion-content class=\"padding content-padding\"><div class=\"course-card mylean-card\"><div class=\"list\"><a class=\"item item-thumbnail-left\" href=\"#\" ng-repeat=\"classroom in content.data\"><img src=\"{{ classroom.middlePicture | coverPic}}\"><h2>{{ classroom.title }}</h2><p class=\"bottom\"><span>最近学习时间:{{ classroom.createdTime | coverLearnTime }}</span></p><span class=\"learn-prossbar\"><span class=\"learn-prossbar prossbar\" ng-style=\"{width: classroom.percent }\"></span></span></a></div></div><ion-infinite-scroll ng-if=\"canLoadMore('classroom')\" on-infinite=\"loadMore('classroom')\" distance=\"1%\"></ion-infinite-scroll></ion-content></ion-view>"
  );


  $templateCache.put('/bundles/topxiamobilebundlev2/main/view/mylearn_course.html',
    "<ion-view><ion-content class=\"padding content-padding\"><div class=\"course-card mylean-card\"><div class=\"list\"><a class=\"item item-thumbnail-left\" href=\"#\" ng-repeat=\"course in content.data\"><img src=\"{{ course.middlePicture | coverPic}}\"><h2>{{ course.title }}</h2><p class=\"bottom\"><span>上次学习时间:{{ course.startTime | coverLearnTime }}</span></p><span class=\"learn-prossbar\"><span class=\"learn-prossbar prossbar\" ng-style=\"course | coverLearnProsser\"></span></span></a></div></div><ion-infinite-scroll ng-if=\"canLoadMore('course')\" on-infinite=\"loadMore('course')\" distance=\"1%\"></ion-infinite-scroll></ion-content></ion-view>"
  );


  $templateCache.put('/bundles/topxiamobilebundlev2/main/view/mylearn_live.html',
    "<ion-view><ion-content class=\"padding content-padding\"><div class=\"course-card mylean-card\"><div class=\"list\"><a class=\"item item-thumbnail-left\" href=\"#\" ng-repeat=\"course in content.data\"><img src=\"{{ course.middlePicture | coverPic}}\"><h2>{{ course.title }}</h2><p class=\"bottom\"><span>下次直播时间:{{ course.liveStartTime | coverLearnTime }}</span></p></a></div></div><ion-infinite-scroll ng-if=\"canLoadMore('live')\" on-infinite=\"loadMore('live')\" distance=\"1%\"></ion-infinite-scroll></ion-content></ion-view>"
  );


  $templateCache.put('/bundles/topxiamobilebundlev2/main/view/note.html',
    "<div class=\"ui-bar\" ui-bar><div class=\"ui-bar-bg\"><button class=\"ui-btn btn-outline iconfont icon-fanhui\" back=\"go\"></button><h3 class=\"title\">笔记详情</h3></div></div><div class=\"top-header ui-question\"><section class=\"ui-panel ui-panel-card ui-question-panel\"><h2 class=\"ui-arrowlink ui-nowrap\" ng-href=\"#/lesson/{{ note.courseId }}/{{ note.lessonId }}\">{{ note.lessonTitle }}</h2><ul class=\"ui-list ui-list-text ui-border-t\"><li><h4>{{ note.title }}</h4></li><li><div class=\"ui-list-info\"><div class=\"ui-question-panel content\" ng-bind-html=\"note.content\"></div></div></li></ul></section></div>"
  );


  $templateCache.put('/bundles/topxiamobilebundlev2/main/view/question.html',
    "<div class=\"ui-bar\" ui-bar><div class=\"ui-bar-bg\"><button class=\"ui-btn btn-outline iconfont icon-fanhui\" back=\"go\"></button><h3 class=\"title\">问答详情</h3></div></div><div class=\"top-header ui-question\"><section class=\"ui-panel ui-panel-card ui-question-panel\"><h2 class=\"ui-arrowlink\" ng-href=\"#/course/{{ thread.courseId }}\">{{ thread.courseTitle }}</h2><ul class=\"ui-list ui-list-text ui-border-tb\"><li><h4>{{ thread.title }}</h4></li><li><div class=\"ui-avatar\"><img ng-src=\"{{ thread.user.mediumAvatar }}\" img-error=\"avatar\"></div><div class=\"ui-list-info\"><h4 class=\"ui-nowrap\">{{ thread.user.nickname }}</h4><p class=\"ui-nowrap\">{{ thread.latestPostTime | coverLearnTime }}</p></div></li><li><div class=\"ui-list-info\"><div class=\"ui-question-panel content\" ng-bind-html=\"thread.content\"></div><div class=\"ui-question-panel tool-bar\"><span><i class=\"iconfont icon-visibility\"></i> {{ thread.hitNum }}</span> <span><i class=\"iconfont icon-modecomment\"></i> {{ thread.postNum }}</span></div></div></li></ul></section><section class=\"ui-panel ui-panel-card ui-question-panel\"><h2><span>教师回答({{ teacherPosts.length }})</span></h2><ul class=\"ui-list ui-list-text\" ng-repeat=\"teacherPost in teacherPosts\"><li><div class=\"ui-avatar-s\"><img ng-src=\"{{ teacherPost.user.mediumAvatar}}\" img-error=\"avatar\"></div><div class=\"ui-list-info\"><h4 class=\"ui-nowrap\">{{ teacherPost.user.nickname }}</h4><p class=\"ui-nowrap\">{{ teacherPost.latestPostTime | coverLearnTime }}</p></div></li><li class=\"ui-border-b\"><div class=\"ui-list-info ui-question-panel content\" ng-bind-html=\"teacherPost.content\"></div></li></ul></section><section class=\"ui-panel ui-panel-card ui-question-panel ui-border-t\" style=\"margin-top : -1px\"><h2><span>所有回答({{ threadPosts.length }})</span></h2><ul class=\"ui-list ui-list-text\" ng-repeat=\"threadPost in threadPosts\"><li><div class=\"ui-avatar-s\"><img ng-src=\"{{ threadPost.user.mediumAvatar}}\" img-error=\"avatar\"></div><div class=\"ui-list-info\"><h4 class=\"ui-nowrap\">{{ threadPost.user.nickname }}</h4><p class=\"ui-nowrap\">{{ threadPost.latestPostTime | coverLearnTime }}</p></div></li><li class=\"ui-border-b\"><div class=\"ui-list-info ui-question-panel content\" ng-bind-html=\"threadPost.content\"></div></li></ul></section></div><!-- \n" +
    "<ion-header-bar class=\"bar-positive\">\n" +
    "<a class=\"button button-icon iconfont icon-fanhui\" back=\"go\">\n" +
    "</a>\n" +
    "\n" +
    "<h1 class=\"title\">问答详情</h1>\n" +
    "</ion-header-bar>\n" +
    "<ion-content class=\"question\">\n" +
    "    <div class=\"list\">\n" +
    "      <a class=\"item item-icon-left item-icon-right lessonlabel\" href=\"#\">\n" +
    "        <i class=\"icon\"></i>\n" +
    "        {{ thread.title }}\n" +
    "        <i class=\"icon iconfont icon-chevronright\"></i>\n" +
    "    </a>\n" +
    "    <a class=\"item title\">\n" +
    "        {{ thread.courseTitle }}\n" +
    "    </a>\n" +
    "\n" +
    "    <a class=\"item item-avatar question-content\" href=\"#\">\n" +
    "      \n" +
    "      <img ng-src=\"{{ thread.user.mediumAvatar }}\">\n" +
    "      <div class=\"userinfo\">\n" +
    "          <h2>{{ thread.user.nickname }}</h2>\n" +
    "          <p>{{ thread.latestPostTime }}</p>\n" +
    "      </div>\n" +
    "      <div class=\"content\" ng-bind-html=\"thread.content\">\n" +
    "      </div>\n" +
    "\n" +
    "      <p class=\"hit\">\n" +
    "        <span>\n" +
    "              <i class=\"iconfont icon-visibility\"></i>\n" +
    "              {{ thread.hitNum }}\n" +
    "        </span>\n" +
    "        <span>\n" +
    "              <i class=\"iconfont icon-modecomment\"></i>\n" +
    "              {{ thread.postNum }}\n" +
    "        </span>\n" +
    "      </p>\n" +
    "    </a>\n" +
    "    <div class=\"list theadpost\">\n" +
    "      <p class=\"postlabel\">所有回答({{ threadPosts.length }})</p>\n" +
    "      \n" +
    "      <a class=\"item item-avatar\" ng-repeat=\"threadPost in threadPosts\">\n" +
    "            <img ng-src=\"{{ threadPost.user.mediumAvatar}}\">\n" +
    "            <div class=\"userinfo\">\n" +
    "              <h2>{{ threadPost.user.nickname }}</h2>\n" +
    "              <p>{{ threadPost.createdTime }}</p>\n" +
    "            </div>\n" +
    "            <div class=\"content\" ng-bind-html=\"threadPost.content\">\n" +
    "            </div>\n" +
    "      </a>\n" +
    "      <ion-infinite-scroll\n" +
    "          ng-if=\"!threadPosts\"\n" +
    "          on-infinite=\"loadTheadPost()\"\n" +
    "          distance=\"1%\">\n" +
    "      </ion-infinite-scroll>\n" +
    "    </div>\n" +
    "\n" +
    "  </div>\n" +
    "  </ion-content>\n" +
    "\n" +
    "</div> -->"
  );


  $templateCache.put('/bundles/topxiamobilebundlev2/main/view/regist.html',
    "<div class=\"ui-bar\" ui-bar><div class=\"ui-bar-bg\"><button class=\"ui-btn btn-outline iconfont icon-fanhui\" back=\"go\"></button><h3 class=\"title\">注 册</h3></div></div><div class=\"ui-tab tab-top sign login\" ui-tab><ul class=\"ui-tab-nav ui-border-b\"><li><span class=\"line\">手机号注册</span></li><li><span>邮箱注册</span></li></ul><ul class=\"ui-tab-content tab-display top-tab-header\" style=\"padding-top : 8px\"><li><div class=\"ui-form\"><div class=\"ui-form-item ui-form-item-r ui-border-b\"><input type=\"text\" maxlength=\"15\" placeholder=\"手机号\" ng-model=\"user.phone\"> <button type=\"button\" class=\"ui-border-l ui-btn-code\" ng-click=\"sendSmsCode(user.phone)\">发送验证码</button> <a class=\"ui-icon-close\" ng-show=\"user.phone.length > 0\" ng-click=\"user.phone = '' \"></a></div><div class=\"ui-form-item ui-form-item-pure ui-border-b margin-t\"><input type=\"password\" placeholder=\"验证码\" ng-model=\"user.code\"> <a href=\"#\" class=\"ui-icon-close\" ng-show=\"user.code.length > 0\" ng-click=\"user.code = '' \"></a></div><div class=\"ui-form-item ui-form-item-pure ui-border-b margin-t\"><input type=\"password\" placeholder=\"密码\" ng-model=\"user.password\"> <a href=\"#\" class=\"ui-icon-close\" ng-show=\"user.password.length > 0\" ng-click=\"user.password = '' \"></a></div><div class=\"ui-btn-wrap padding-none margin-t\"><button class=\"ui-btn-lg btn-green\" ng-click=\"registWithPhone(user)\">注 册</button></div></div></li><li><div class=\"ui-form\"><div class=\"ui-form-item ui-form-item-pure ui-border-b\"><input type=\"email\" placeholder=\"邮箱\" ng-model=\"user.email\"> <a href=\"#\" class=\"ui-icon-close\" ng-show=\"user.email.length > 0\" ng-click=\"user.email = '' \"></a></div><div class=\"ui-form-item ui-form-item-pure ui-border-b margin-t\"><input type=\"password\" placeholder=\"密码\" ng-model=\"user.password\"> <a href=\"#\" class=\"ui-icon-close\" ng-show=\"user.password.length > 0\" ng-click=\"user.password = '' \"></a></div><div class=\"ui-btn-wrap padding-none margin-t\"><button class=\"ui-btn-lg btn-green\" ng-click=\"registWithEmail(user)\">注 册</button></div></div></li></ul></div>"
  );


  $templateCache.put('/bundles/topxiamobilebundlev2/main/view/regist_email.html',
    "<ion-view><ion-content class=\"padding\"><div class=\"list sign\"><label class=\"item item-input\"><input type=\"email\" placeholder=\"邮箱\" ng-blur=\"emailstyle=''\" ng-focus=\"emailstyle='active'\" ng-model=\"user.email\" ng-class=\"emailstyle\"></label><label class=\"item item-input\"><input type=\"password\" placeholder=\"密码\" ng-blur=\"passstyle=''\" ng-focus=\"passstyle='active'\" ng-model=\"user.password\" ng-class=\"passstyle\"></label></div><div class=\"sign-padding\"><button class=\"button button-block button-green\" ng-click=\"registWithEmail(user)\">注 册</button></div></ion-content></ion-view>"
  );


  $templateCache.put('/bundles/topxiamobilebundlev2/main/view/regist_phone.html',
    "<ion-view><ion-content class=\"padding content-padding\"><div class=\"list sign\"><a class=\"item item-input\"><input type=\"text\" maxlength=\"14\" placeholder=\"手机号\" ng-blur=\"nkstyle=''\" ng-focus=\"nkstyle='active'\" ng-model=\"user.phone\" ng-class=\"nkstyle\"> <button class=\"button button-code\" ng-click=\"sendSmsCode(user.phone)\">发送验证码</button></a><label class=\"item item-input\"><input type=\"password\" placeholder=\"验证码\" ng-blur=\"codestyle=''\" ng-model=\"user.code\" ng-focus=\"codestyle='active'\" ng-class=\"codestyle\"></label><label class=\"item item-input\"><input type=\"password\" placeholder=\"密码\" ng-blur=\"passstyle=''\" ng-model=\"user.password\" ng-focus=\"passstyle='active'\" ng-class=\"passstyle\"></label></div><div class=\"sign-padding\"><button class=\"button button-block button-green\" ng-click=\"registWithPhone(user)\">注 册</button></div></ion-content></ion-view>"
  );


  $templateCache.put('/bundles/topxiamobilebundlev2/main/view/search.html',
    "<div class=\"ui-bar search-bar\"><div class=\"ui-bar-bg\"><button class=\"ui-btn btn-outline iconfont icon-fanhui\" back=\"go\"></button><div class=\"title\"><div class=\"ui-searchbar-wrap ui-border-b focus\"><div class=\"ui-searchbar ui-border-radius\" ng-click=\"showSearch()\"><i class=\"ui-icon-search\"></i><div class=\"ui-searchbar-text\">搜索课程</div><div class=\"ui-searchbar-input\"><input ng-model=\"search\" type=\"tel\" placeholder=\"搜索课程\" autocapitalize=\"off\"></div><i class=\"ui-icon-close\" ng-click=\"search = '' \"></i></div><button class=\"ui-searchbar-cancel\" ng-click=\"seach()\">{{ search.length > 0 ? '搜索' : '取消' }}</button></div></div></div></div><div class=\"top-header ui-scroller course-card\" ui-scroll data=\"searchDatas\" on-infinite=\"loadMore()\"><ul class=\"ui-list ui-border-tb\"><li class=\"ui-border-t\" ng-repeat=\"course in searchDatas\" ui-sref=\"course({ courseId : course.id })\"><div class=\"ui-list-img\"><img ng-src=\"{{ course.middlePicture }} \" img-error=\"course\"></div><div class=\"ui-list-info\"><h2 class=\"ui-nowrap ui-list-title\">{{ course.title }}</h2><p class=\"bottom price-body\"><span class=\"origin-price\">{{ course.price | formatPrice }} <s class=\"discount-color\" ng-if=\"course.discountId > 0\">{{ course.originPrice | formatPrice }}</s></span> <span class=\"right\"><i class=\"iconfont icon-person\"></i> {{ course.studentNum }}人在学</span></p></div></li></ul><list-empty-view data=\"searchDatas\" title=\"没有搜索到神马\"></list-empty-view><div class=\"ui-loading-wrap\" ng-show=\"canLoad\"><p>正在加载中...</p><i class=\"ui-loading\"></i></div></div>"
  );


  $templateCache.put('/bundles/topxiamobilebundlev2/main/view/setting.html',
    "<div class=\"ui-bar\" ui-bar><div class=\"ui-bar-bg\"><button class=\"ui-btn btn-outline iconfont icon-fanhui\" back=\"go\"></button><h3 class=\"title\">设 置</h3></div></div><div class=\"top-header ui-userinfo\"><ul class=\"ui-list ui-list-text ui-list-link ui-list-cover\"><li class=\"ui-border-b\"><h4 class=\"ui-nowrap\">扫描进入网校</h4></li></ul><div ng-if=\"isShowLogoutBtn\" class=\"ui-btn-wrap\" style=\"margin-top : 16px\"><button class=\"ui-btn-lg btn-red\" ng-click=\"logout()\">退出账号</button></div></div>"
  );


  $templateCache.put('/bundles/topxiamobilebundlev2/main/view/teacher_list.html',
    "<div class=\"ui-bar\" ui-bar><div class=\"ui-bar-bg\"><button class=\"ui-btn btn-outline iconfont icon-fanhui\" back=\"go\"></button><h3 class=\"title\">课程教师</h3></div></div><div class=\"top-header\"><ul class=\"ui-list ui-list-text ui-list-active\"><li class=\"ui-border-t\" ng-repeat=\"user in users\" ui-sref=\"userInfo({ userId : user.id })\"><div class=\"ui-avatar-s\"><img ng-src=\"{{ user.mediumAvatar | coverAvatar }}\"></div><div class=\"ui-list-info\"><h4 class=\"ui-nowrap\">{{ user.nickname }}</h4><p class=\"ui-nowrap\">{{ user.title }}</p></div></li></ul><list-empty-view class=\"lesson-empty\" data=\"users\" title=\"该课程暂无教师\"></list-empty-view></div>"
  );


  $templateCache.put('/bundles/topxiamobilebundlev2/main/view/user_info.html',
    "<div class=\"ui-bar transparent\" ng-class=\"uiBarTransparent ? 'transparent' : '' \" ui-bar><div class=\"ui-bar-bg\"><button class=\"ui-btn btn-outline iconfont icon-fanhui\" back=\"go\"></button><h3 class=\"title\">用户信息</h3></div></div><div class=\"ui-details ui-scroller\" ui-scroll data=\"userinfo\" on-scroll=\"changeTabStatus(headTop, scrollTop)\"><ul class=\"ui-list transparent ui-details-panel ui-details-head\"><li><div class=\"ui-details-avatar\"><div class=\"ui-avatar-lg\"><img class=\"avatar\" ng-src=\"{{ userinfo.mediumAvatar | coverAvatar }}\" img-error=\"avatar\"></div><h4 class=\"ui-nowrap\">{{ userinfo.nickname }}</h4><p class=\"ui-nowrap\">{{ userinfo.signature }}</p><button class=\"ui-btn btn-green\" ng-click=\"changeFollowUser()\" ng-if=\"isFollower != null \">{{ isFollower ? \"已关注\" : \"+关注\" }}</button></div></li></ul><div class=\"ui-panel\"><div class=\"ui-border-b ui-details-panel ui-vip\"><ul class=\"ui-list ui-border-b\"><li><ul class=\"ui-grid-trisect\"><li><h4>{{ userinfo.following }}</h4><p>关注</p></li><li><h4>{{ userinfo.follower }}</h4><p>粉丝</p></li><li><h4>{{ courses.length }}</h4><p>课程</p></li></ul></li><li class=\"vip-price-line\"><span>个人介绍</span></li></ul><div class=\"ui-btn-wrap\"><h4 ng-bind-html=\"userinfo.about\"></h4></div></div><div class=\"course-card ui-border-t\" style=\"margin-top : 16px\"><h4>{{ isTeacher ? '在教课程' : '在学课程' }}</h4><ul class=\"ui-list ui-list-link ui-border-tb\"><li class=\"ui-border-t\" ng-repeat=\"course in courses\" ui-sref=\"course({ courseId : course.id })\"><div class=\"ui-list-img\"><img class=\"full-image\" ng-src=\"{{ course.largePicture }}\" img-error=\"course\"></div><div class=\"ui-list-info\"><h2 class=\"ui-nowrap ui-list-title\">{{ course.title }}</h2><p class=\"bottom price-body\"><span class=\"origin-price\">{{ course.price | formatPrice }} <s class=\"discount-color\" ng-if=\"course.discountId > 0\">{{ course.originPrice | formatPrice }}</s></span></p></div></li></ul></div></div></div>"
  );


  $templateCache.put('/bundles/topxiamobilebundlev2/main/view/vip_list.html',
    "<div class=\"ui-bar\" ui-bar><div class=\"ui-bar-bg\"><button class=\"ui-btn btn-outline iconfont icon-fanhui\" back=\"go\"></button><h3 class=\"title\">开通会员</h3></div></div><div class=\"top-header ui-vip\"><ul class=\"ui-list ui-vip-header ui-border-tb\"><li class=\"ui-border-t ui-vip-avatar\"><div class=\"ui-avatar\"><img ng-src=\"{{ user.mediumAvatar | coverAvatar }}\" img-error=\"avatar\"></div><div class=\"ui-list-info\"><h4 class=\"ui-nowrap\">{{ data.user.nickname }}</h4><p class=\"ui-nowrap\">{{ data.user.vip ? data.vips[data.user.vip.levelId].name : \"暂时还不是会员\" }}</p></div></li><li class=\"vip-price-line\"><span>其他方式登录</span></li></ul><div class=\"ui-panel\" ng-repeat=\"vip in data.vips\"><ul class=\"ui-list\"><li class=\"ui-border-b\"><div class=\"ui-avatar\"><img ng-src=\"{{ vip.picture }}\" img-error=\"vip\"></div><div class=\"ui-list-info\"><h4 class=\"ui-nowrap\">{{ vip.name }}</h4></div><div class=\"ui-list-action ui-vip-price\"><sup>¥</sup> <span>{{ vip.monthPrice }}</span> 1个月</div></li><li class=\"ui-border-b\"><div class=\"ui-list-info\"><div class=\"ui-vip-content\">{{ vip.description | blockStr }}</div><p ng-if=\"! vip.description || vip.description.length == 0\">没有会员简介</p></div></li></ul><div class=\"ui-btn-wrap ui-border-b\"><a class=\"ui-btn-lg btn-green\" href=\"#/vippay/{{ vip.id }}\">立即开通</a></div></div></div>"
  );


  $templateCache.put('/bundles/topxiamobilebundlev2/main/view/vip_pay.html',
    "<div class=\"ui-bar\" ui-bar><div class=\"ui-bar-bg\"><button class=\"ui-btn btn-outline iconfont icon-fanhui\" back=\"go\"></button><h3 class=\"title\">开通会员</h3></div></div><div class=\"top-header ui-vippay ui-course-pay\"><div class=\"ui-form\"><div class=\"ui-form-item ui-list-divider ui-border-b\">选择开通时长</div><div class=\"ui-form-item ui-vippay-item ui-border-b\"><ul class=\"ui-grid-halve\"><li class=\"ui-border-r\"><a ng-click=\"showPopover($event)\">{{ selectedPayMode.title }} <i class=\"iconfont icon-arrowdropdown\"></i></a><ul class=\"ui-list ui-vippay-modal\" ng-show=\"isShowPayMode\"><li class=\"ui-border-b\" ng-repeat=\"payMode in payModes\" ng-click=\"selectPayMode(payMode)\">{{ payMode.title }}</li></ul></li><li><a class=\"ui-vippay-btn iconfont icon-jian btn-gray\" ng-click=\"sub()\"></a> <a class=\"ui-vippay-btn btn-border\">{{ selectedNum }}</a> <a class=\"ui-vippay-btn iconfont icon-jia btn-green\" ng-click=\"add()\"></a></li></ul></div><div class=\"ui-btn-wrap\"><div class=\"pay-body\">需支付:<span class=\"pay-price\">{{ totalPayPrice | formatPrice}}</span></div><button class=\"ui-btn-lg btn-yellow\" ng-click=\"payVip()\">去支付</button></div></div></div>"
  );

}]);
