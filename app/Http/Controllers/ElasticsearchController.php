<?php

namespace App\Http\Controllers;
use App\Models\Pdf;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Process\Process;
use App\Models\indexName;
use Elastic\Elasticsearch\ClientBuilder;
use Illuminate\Http\Request;
use Smalot\PdfParser\Parser;
use elastic\Elasticsearch;
use Symfony\Component\Process\Exception\ProcessFailedException;
class ElasticsearchController extends Controller
{
    public function createIndex()
    {
        $client = ClientBuilder::create()
        ->setHosts([config('elasticsearch.hosts')])
        ->setBasicAuthentication('taha', 'taha123')
        ->build();

    $indexName = 'book';

    $params = [
        'index' => $indexName,
        'body' => [
            'settings' => [
                'analysis' => [
                    'filter' => [
                        'arabic_stop' => [
                            'type' => 'stop',
                            'stopwords' => '_arabic_',
                        ],
                        'arabic_keywords' => [
                            'type' => 'keyword_marker',
                            'keywords' => ['مثال'], // Add any specific keywords
                        ],
                        'arabic_stemmer' => [
                            'type' => 'stemmer',
                            'language' => 'arabic',
                        ],
                        'custom_word_delimiter' => [
                            'type' => 'word_delimiter',
                            'preserve_original' => true
                        ],
                    ],
                    'analyzer' => [
                        'arabic' => [
                            'tokenizer' => 'standard',
                            'filter' => [
                                'lowercase',
                                'decimal_digit',
                                'arabic_stop',
                                'arabic_normalization',
                                'arabic_keywords',
                                'arabic_stemmer',
                                'custom_word_delimiter'
                            ],
                        ],
                    ],
                ],
            ],
            'mappings' => [
                'properties' => [
                    'pdf_content' => [
                        'type' => 'text',
                        'analyzer' => 'arabic',
                    ],
                ],
            ],
        ],
    ];

    $client->indices()->create($params);

        return response()->json(['message' => 'Index created successfully']);
    }

    public function uploadAndIndex(Request $request)
    {
        $this->validate($request, [
            'pdf' => 'required|mimes:pdf',
            'title' => 'required|string|max:255',
        ]);

        // Upload the PDF file to public storage
        $pdfFile = $request->file('pdf');
        $pdfFileName = $pdfFile->storeAs('pdfs', $pdfFile->getClientOriginalName(), 'public');

        // Extract text from the PDF using pdftotext
        $process = new Process(['D:\download\poppler-23.08.0\Library\bin\pdftotext', '-', '-']);
        $process->setInput(file_get_contents($pdfFile->getPathname())); // Use file contents
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException($process->getErrorOutput());
        }

        $content = $process->getOutput();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException($process->getErrorOutput());
        }

        $content = $process->getOutput();

        // Create Elasticsearch client
        $client = ClientBuilder::create()
            ->setHosts([config('elasticsearch.hosts')])
            ->setBasicAuthentication('taha', 'taha123') // Replace with your credentials
            ->build();

        $indexName = 'book';

        // Index the document with the PDF path and content
        $params = [
            'index' => $indexName,
            'body' => [
                'title' => $request->input('title'),
                'pdf_content' => $content,
                'pdf_path' =>   $pdfFileName, // Set the PDF path
            ],
        ];

        $response = $client->index($params);

        return response()->json([
            'message' => 'PDF uploaded and indexed successfully',
            'document_id' => $response['_id'],
            'pdf_path' => asset($pdfFileName),
            ])->header('Content-Type', 'application/pdf');
    }

    public function search(Request $request)
    {
        $this->validate($request, [
            'query' => 'required|string|max:255',
        ]);

        $client = ClientBuilder::create()
            ->setHosts([config('elasticsearch.hosts')])
            ->setBasicAuthentication('taha', 'taha123')
            ->build();

        $indexName = 'book';

        $query = $request->input('query');
        $params = [
            'index' => $indexName,
            'body' => [
                'query' => [
                    'match_phrase' => [
                        'pdf_content' => [
                            'query' => $query,
                            'slop' => 1 // Adjust this value as needed
                        ],
                    ],
                ],
                'highlight' => [
                    'order' => 'score',
                    'fields' => [
                        'pdf_content' => [
                            'fragment_size' => 110,
                            'number_of_fragments' => 20,
                            'highlight_query' => [
                                'match_phrase' => [
                                    'pdf_content' => $query,
                                ],
                            ],
                            'pre_tags' => ['<em style="color:red">'],
                            'post_tags' => ['</em>'],
                        ],
                    ],
                    'type' => 'unified',
                ],
            ],
        ];

        $response = $client->search($params);

        $hits = $response['hits']['hits'];

        $results = [];

        foreach ($hits as $hit) {
            $highlight = $hit['highlight'];
            $cleanedTitle = strip_tags($hit['_source']['title']);
            $cleanedContent = strip_tags($hit['_source']['pdf_content']);
            $filePath = $hit['_source']['pdf_path']; // Adjust this based on your data structure

            $result = [
                'title' => $cleanedTitle,
                'content' => $cleanedContent,
                'highlight' => $highlight,
                'file_path' => $filePath,
            ];

            $results[] = $result;
        }

        return response()->json([
            'message' => 'Search results retrieved successfully',
            'results' => $results,
        ]);
    }



public function getAllTitlesAndContent(Request $request)
{
    try {
        // Create Elasticsearch client
        $client = ClientBuilder::create()
            ->setHosts([config('elasticsearch.hosts')])
            ->setBasicAuthentication('taha', 'taha123')
            ->build();

        $indexName = 'book';

        // Define the match_all query
        $params = [
            'index' => $indexName,
            'body' => [
                'query' => [
                    'match_all' => new \stdClass(),
                ],
            ],
        ];

        // Execute the search
        $response = $client->search($params);

        // Process search results
        $hits = $response['hits']['hits'];
        $titlesAndContent = [];

        foreach ($hits as $hit) {
            $title = $hit['_source']['title'];
            $content = $hit['_source']['pdf_content'];
            $titlesAndContent[] = ['title' => $title, 'content' => $content];
        }

        // Return JSON response
        return new JsonResponse(['titlesAndContent' => $titlesAndContent]);
    } catch (\Exception $e) {
        // Handle exceptions
        return new JsonResponse(['error' => $e->getMessage()], 500);
    }
}
public function deleteDocumentsByTitle(Request $request)
{
    $this->validate($request, [
        'title' => 'required|string|max:255',
    ]);

    try {
        $client = ClientBuilder::create()
            ->setHosts([config('elasticsearch.hosts')])
            ->setBasicAuthentication('taha', 'taha123')
            ->build();

        $indexName = 'book';
        $title = $request->input('title');

        $params = [
            'index' => $indexName,
            'body' => [
                'query' => [
                    'match' => ['title' => $title]
                ]
            ]
        ];

        $response = $client->deleteByQuery($params);

        return response()->json(['message' => 'Documents deleted successfully']);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}



}

