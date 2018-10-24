<?php

namespace App\Services\v1;

use Illuminate\Support\Facades\DB;
use App\Classes\QueryBuilder;

class QuoteesService extends QueryBuilder
{
    protected $table = 'quotees';

    protected $supportedIncludes = [
        'quotes' => 'quote_count'
    ];

    protected $clauseProperties = [
        'likeClauses' => [
        ],
        'matchClauses' => [
            'quotees.id' => 'quotee_id',
            'nationalities.id' => 'nationality_id',
            'professions.id' => 'profession_id',
            'quotees.quotee_name' => 'quotee',
            'nationalities.nationality_name' => 'nationality',
            'professions.profession_name' => 'profession'
        ]

    ];

    protected $sortingFields = [
        'quote_count' => 'quote_count',
        'quotee_name' => 'name'
    ];

    protected $requiredIncludes = [
        'quotes' => [
            'quote_count'
        ]
    ];

    public function getQuotees($parameters)
    {
        // Check if parameters is defined
        if (empty($parameters)) {
            // Get all quotes
            return $this->filterQuotes($this->buildQuery()->get());
        }

        $includes = $this->getIncludes($parameters);
        $clauses = $this->getWhereClauses($parameters);
        $sorting = $this->getSorting($parameters);

        $query = $this->buildQuery($includes, $clauses, $sorting);

        return $this->filterQuotes($query->get(), $includes);
    }

    // Filters what fields are shown
    protected function filterQuotes($quotees, $params = [])
    {
        // Array to hold the data
        $data = [];
        // Iterate over results
        // and choose what fields to keep
        foreach ($quotees as $quotee) {
            $entry = [
                'quotee_id' => $quotee->quotee_id,
                'quotee_name' => $quotee->quotee_name,
                'biography_link' => $quotee->biography_link,
                'profession' => [
                    'id' => $quotee->profession_id,
                    'name' => $quotee->profession_name
                ],
                'nationality' => [
                    'id' => $quotee->nationality_id,
                    'name' => $quotee->nationality_name
                ]
            ];

            if (in_array('quote_count', $params)) {
                $entry['quote_count'] = $quotee->quote_count;
            }
            // Add entry to data array
            $data[] = $entry;
        }

        return $data;
    }

    protected function addSelects(&$query, $includes)
    {
        $quoteesSelect = ['quotees.id as quotee_id','quotees.quotee_name','quotees.biography_link'];
        $nationalitiesSelect = ['nationalities.id as nationality_id', 'nationalities.nationality_name as nationality_name'];
        $professionsSelect = ['professions.id as profession_id', 'professions.profession_name as profession_name'];
        $includeSelects = [];

        if (!empty($includes)) {
            foreach ($includes as $key=>$value) {
                if ($key == 'quotes') {
                    $includeSelects[] = DB::raw('COUNT(quotes.id) as quote_count');
                    continue;
                }
                $includeSelects[] = $key . '.id as ' . $value . '_id';
                $includeSelects[] = $key . '.' . $value . '_name';
            }
        }

        $selectArray = array_merge($quoteesSelect, $nationalitiesSelect, $professionsSelect, $includeSelects);

        $query->select($selectArray);
    }

    protected function addJoins(&$query, $includes)
    {
        $query  ->join('nationalities', 'quotees.nationality_id', 'nationalities.id')
                ->join('professions', 'quotees.profession_id', 'professions.id');

        if (isset($includes['quotes'])) {
            $query->join('quotes', 'quotes.quotee_id', 'quotees.id');
            $query->groupBy('quotee_id');
        }
    }
}
