<?php

namespace Liip\SoapRecorderBundle\DataCollector;

use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SOAPDataCollector extends DataCollector
{
    protected $config;
    
    public function __construct($container)
    {
        $this->config = $container->getParameter('liip_soap_recorder_config');
    }

    /**
     * {@inheritdoc}
     */
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        // If the profiler is disable, just return
        if ($this->config['enable_profiler'] !== true) {
            return;
        }

        $requests = $this->fetchSOAPRecordsFromFolder($this->config['request_folder']);
        $responses = $this->fetchSOAPRecordsFromFolder($this->config['response_folder']);
        $times = $this->fetchSOAPRecordsFromFolder($this->config['response_folder'], 'mst');
        
        $this->data = array(
            'requests'  => $requests,
            'responses' => $responses,
            'times'     => $times,
            'count'     => count($requests),
        );
    }

    /**
     * Returns the collector name.
     *
     * @return string   The collector name.
     */
    public function getName()
    {
        return 'soap';
    }

    /**
     * Returns the number of recorded SOAP calls, previously.
     * recorded by getRequestCount() method.
     *
     * @return int   The number of recorded calls.
     */
    public function getCount()
    {
        return $this->data['count'];
    }

    /**
     * Returns the recorded requests, previously.
     * recorded by fetchSOAP() method.
     *
     * @return array   Array containing all recorded requests.
     */
    public function getRequests()
    {
        return $this->data['requests'];
    }

    /**
     * Returns the recorder responses, previously.
     * recorded by fetchSOAP() method.
     *
     * @return int   Array containing all recorded responses.
     */
    public function getResponses()
    {
        return $this->data['responses'];
    }
    
    /**
     * Returns the recorder time responses, previously.
     * recorded by fetchSOAP() method.
     *
     * @return int   Array containing all recorded times.
     */
    public function getTimes()
    {
        return $this->data['times'];
    }
    
    /**
     * Get the total request time
     * 
     * @return float time in ms
     */
    public function getTime()
    {
        $time = 0;

        foreach ($this->data['times'] as $requestTime) {
            $time += $requestTime;
        }

        return $time;
    }

    /**
     * Fetch the content of all files inside a folder.
     *
     * @return array  Content of the files
     */
    protected function fetchSOAPRecordsFromFolder($folder, $extension = 'xml')
    {
        $records = array();
        foreach (scandir($folder) as $filename) {

            $ext = substr($filename, -3);
            // Ignore sub folders, hidden files and files of wrong type.
            if (is_dir($filename) || substr($filename, 0, 1) === '.' || $ext != $extension) {
                continue;
            }

            $filePath = $folder.'/'.$filename;
            $content = file_get_contents($filePath);

            
            // XML Formatting
            if ($ext == 'xml' && strlen($content) > 0){
                $doc = new \DOMDocument;
                $doc->loadXML($content, LIBXML_NOERROR);
                $doc->formatOutput = TRUE;
                $content = $doc->saveXML();
            }

            // Saved and remove the original file
            $records[] = $content;
            unlink($filePath);
        }

        return $records;
    }
}