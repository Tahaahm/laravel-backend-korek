<?php

namespace App\Http\Controllers;

use Elastic\Elasticsearch\ClientBuilder;
use Elastica\Client as ElasticaClient;

class ClinetController extends Controller
{
    protected $elasticsearch;

    protected $elastica;
    public function __construct(){
        $this->elasticsearch=ClientBuilder::create()->build();

        $elasticaConfig=[
            'host'=>'localhost',
            'port'=>9200,
            'index'=>'pets'
        ];

    }
}
