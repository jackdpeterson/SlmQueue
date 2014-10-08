<?php

namespace SlmQueue\Queue;

use SlmQueue\Job\JobInterface;
use SlmQueue\Job\JobPluginManager;

/**
 * AbstractQueue
 */
abstract class AbstractQueue implements QueueInterface
{
    /**
     * @var JobPluginManager
     */
    protected $jobPluginManager;

    /**
     * @var string
     */
    protected $name;

    /**
     * @param string           $name
     * @param JobPluginManager $jobPluginManager
     */
    public function __construct($name, JobPluginManager $jobPluginManager)
    {
        $this->name             = $name;
        $this->jobPluginManager = $jobPluginManager;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritDoc}
     */
    public function getJobPluginManager()
    {
        return $this->jobPluginManager;
    }

    /**
     * Create a job instance based on serialized input
     *
     * Instantiate a job based on a serialized data string. The string
     * is a JSON string containing job name, content and metadata. Use
     * the decoded JSON value to create a job instance, configure it
     * and return it.
     *
     * @param  string $string
     * @param  array  $metadata
     * @return \SlmQueue\Job\JobInterface
     */
    public function unserializeJob($string, array $metadata = array())
    {
        // To keep compatibility with SlmQueue 0.3, will be removed in 0.5 in favour of __name__ only

        $data     =  json_decode($string, true);
        $name     =  isset($data['__name__']) ? $data['__name__'] : $data['name'];
        $metadata += $data['metadata'];
        $content  =  unserialize($data['content']);

        /** @var $job \SlmQueue\Job\JobInterface */
        $job = $this->getJobPluginManager()->get($name);

        $job->setContent($content);
        $job->setMetadata($metadata);

        if ($job instanceof QueueAwareInterface) {
            $job->setQueue($this);
        }

        return $job;
    }

    /**
     * Serialize job to allow persistence
     *
     * The serialization format is a JSON object with keys "content",
     * "metadata" and "__name__". When a job is fetched from the SL, a job name
     * will be set and be available as metadata. An invokable job has no service
     * name and therefore the FQCN will be used.
     *
     * @param  JobInterface $job The job to persist
     * @return string
     */
    public function serializeJob(JobInterface $job)
    {
        $name = $job->getMetadata('__name__');
        $data = array(
            '__name__' => $name ?: get_class($job),
            'content'  => serialize($job->getContent()),
            'metadata' => $job->getMetadata()
        );

        return json_encode($data);
    }
}
