<?php

namespace AppBundle\Extension;

class ExtensionManager
{
    protected $extensions = array();

    protected $questionTypes = array();

    protected $payments = array();

    protected $activities = array();

    protected $callbacks = array();

    public function addExtension(ExtensionInterface $extension)
    {
        $this->extensions[] = $extension;

        $this->questionTypes = array_merge($this->questionTypes, $extension->getQuestionTypes());
        $this->payments = array_merge($this->payments, $extension->getPayments());
        $this->activities = array_merge($this->activities, $extension->getActivities());
        $this->callbacks = array_merge($this->callbacks, $extension->getCallbacks());
    }

    public function getQuestionTypes()
    {
        return $this->questionTypes;
    }

    public function getPayments()
    {
        return $this->payments;
    }

    public function getActivities()
    {
        return $this->activities;
    }

    public function getCallbacks()
    {
        return $this->callbacks;
    }
}
