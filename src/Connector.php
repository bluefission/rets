<?php
namespace BlueFission\Rets;

class Connector {
    private $connection;
    protected $_table_meta = array();
    protected $_system_meta = array();
    protected $_class_meta = array();
    public function connect( $url, $username, $password ) {
        $phretsconfig = new \PHRETS\Configuration();
        $phretsconfig->setLoginUrl($url);
        $phretsconfig->setUsername($username);
        $phretsconfig->setPassword($password);
        if (isset($version)) {
            $phretsconfig->setRetsVersion($version);
        }
        if (isset($user_agent)) {
            $phretsconfig->setUserAgent($user_agent);
        }
        $mgr = new \PHRETS\Session($phretsconfig);
        try {
            $mgr->Login();
        } catch (\Exception $e) {
            $phretsconfig->setHttpAuthenticationMethod(\PHRETS\Configuration::AUTH_BASIC );
        }
        try {
            $mgr->Login();
        } catch (\Exception $e) {
            echo "Failed to login, {$e->getMessage()}";
        }
        try {
            $metadata = $mgr->GetSystemMetaData();
        } catch (\Exception $e) {
            $metadata = array();
        }
        // die(var_dump($metadata));
        $this->connection = $mgr;
    }
    public function search ( $resource, $class, $query, $settings = array( "Limit" => 10, "Offset" => 0 ) ) {
        $results = $this->connection->Search($resource, $class, $query, $settings);
        return $results;
    }
    public function properties ($resource, $class, $query) {
        $results = null;
        $limit = 500;
        $offset = 0;
        $maxrows = 0;
        $objects = array();
        // $resource = 'Property';
        // $class = 'A';
        // $query = '(LIST_15=|1AQXFJ7CUYMN,1AQXFJ7CX75K)';
        do {
            try {
                
                $options = array(
                    "Limit" => $limit,
                    "Offset" => $offset
                );
                
                $results = $this->search($resource, $class, $query, $options);
                // var_dump($query);
            } catch ( \PHRETS\Exceptions\RETSException $e ) {
                echo $e->getMessage();
                $status = $e->getMessage();
            } catch ( \Exception $e ) {
                echo $e->getMessage();
                $status = $e->getMessage();
            }
            if ($class && is_object($results) && !isset($maxrows))
                $maxrows = $results->getTotalResultsCount();
            
            if ($results) {
                foreach ($results as $record) {
                    
                    $object = $record->toArray();
                    $objects[] = $object;
                }
                $status = 'Success';
            }
            if ( $maxrows >= $offset && $maxrows - $offset < $limit ) {
                $offset += ($maxrows - $offset);
            } else {
                $offset += $limit;
            }
        } while (!(isset($maxrows) && $maxrows <= $offset));
        return $objects;
    }

    public function media ( $id, $resource, $class ) {
        $results = false;
        try {
            $results = $this->connection->GetObject($resource, $class, $id, "*", 1 );
            if (!is_object($results[0]) || (strpos($results[0]->getContentType(), 'text/xml') !== false && strpos($results[0]->getContent(), '20409') !== false) ) {
                
                $results = $this->connection->GetObject($resource, 'HiRes', $id);
            }
        } catch ( \Exception $e ) {
            echo "Failed to get image, {$e->getMessage()}";
        }
        return $results;
    }
}