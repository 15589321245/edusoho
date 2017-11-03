<?php

namespace Biz\Xapi\Type;

class AskQuestionType extends Type
{
    const TYPE = 'asked_question';

    public function package($statement)
    {
        $thread = $this->getThreadService()->getThread(0, $statement['target_id']);
        if ($thread['type'] != 'question') {
            return;
        }
        $task = $this->getTaskService()->getTask($thread['taskId']);
        $course = $this->getCourseService()->getCourse($thread['courseId']);
        $courseSet = $this->getCourseSetService()->getCourseSet($thread['courseSetId']);
        $course['description'] = $courseSet['subtitle'];
        $activity = $this->getActivityService()->getActivity($task['activityId'], true);

        if (in_array($activity['mediaType'], array('video', 'audio', 'doc', 'ppt', 'flash'))) {
            $resource = $this->getUploadFileService()->getFile($activity['ext']['mediaId']);
        }

        $object = array(
            'id' => $thread['id'],
            'course' => $course,
            'definitionType' => $this->convertMediaType($task['type']),
            'resource' => empty($resource) ? array() : $resource,
        );

        $actor = $this->getActor($statement['user_id']);

        $result = $thread;

        return $this->createXAPIService()->askQuestion($actor, $object, $result, false);
    }
}
