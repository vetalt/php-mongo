<?php

namespace Sokil\Mongo\DocumentRelationTest;

class OnemanyTargetCollection extends \Sokil\Mongo\Collection
{
    public function getDocumentClassName(array $documentData = null)
    {
        return '\Sokil\Mongo\DocumentRelationTest\OnemanyTargetDocument';
    }
}