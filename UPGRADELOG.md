8.0 升级日志

废弃的api：

	CourseService:
		countMembersByStartTimeAndEndTime
		findMobileVerifiedMemberCountByCourseId
		deleteMemberByCourseIdAndUserId

	CourseMemberDao:
		getMemberCountByUserIdAndCourseTypeAndIsLearned
		getMemberCountByUserIdAndRoleAndIsLearned
		findMembersByUserIdAndCourseTypeAndIsLearned
		findMembersByUserIdAndRoleAndIsLearned
		findStudentsByCourseId
		findTeachersByCourseId
		findMemberCountByCourseIdAndRole
		findCourseMembersByUserId
		findMemberCountNotInClassroomByUserIdAndRole
		findMemberCountByUserIdAndRole
		
    MaterialService:
        findMaterialCountGroupByFileId
        findMaterialsGroupByFileId
        findLessonMaterials
        getMaterialCount
    
    remove UserRoleDict use get('codeages_plugin.dict_twig_extension')->getDict('userRole')