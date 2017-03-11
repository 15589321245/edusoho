<?php

namespace Biz\Question\Type;

use AppBundle\Common\ArrayToolkit;

class Determine implements TypeInterface
{
    public function create($fields)
    {
    }

    public function update($id, $fields)
    {
    }

    public function delete($id)
    {
    }

    public function get($id)
    {
    }

    public function judge($question, $answer)
    {
        $rightAnswer = array_pop($question['answer']);
        $userAnswer = array_pop($answer);

        $status = $userAnswer == $rightAnswer ? 'right' : 'wrong';
        $score = $userAnswer == $rightAnswer ? $question['score'] : 0;

        return array('status' => $status, 'score' => $score);
    }

    public function filter($fields)
    {
        if (!empty($fields['target']) && $fields['target'] > 0) {
            $fields['lessonId'] = $fields['target'];
            unset($fields['target']);
        }
        $fields = ArrayToolkit::parts($fields, array(
            'type',
            'stem',
            'difficulty',
            'userId',
            'answer',
            'analysis',
            'metas',
            'score',
            'categoryId',
            'parentId',
            'copyId',
            'target',
            'courseId',
            'courseSetId',
            'lessonId',
            'subCount',
            'finishedTimes',
            'passedTimes',
            'userId',
            'updatedTime',
            'createdTime',
        ));

        return $fields;
    }
}
