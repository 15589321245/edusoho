<?php

namespace Topxia\Service\Course\Dao;

interface ExerciseDao
{

    public function getExercise($id);

    public function getExerciseByCourseIdAndLessonId($courseId, $lessonId);

    public function addExercise($fields);

    public function updateExercise($id, $fields);

    public function deleteExercise($id);

    public function findExerciseByCourseIdAndLessonIds($courseId, $lessonIds);

}