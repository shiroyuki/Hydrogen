<?php
namespace Shiroyuki\Hydrogen;

class Accessor
{
    public function get(array $nestedData, $selectorString, $separator = '/')
    {
        $vertexList = $this->parseSelectorString($selectorString, $separator);
        $cursor     = $nestedData;
        
        foreach ($vertexList as $vertex) {
            if (in_array($vertex, $cursor)) {
                throw new \RuntimeException('undefined path');
            }
            
            $cursor = $cursor[$vertex];
        }
        
        return $cursor;
    }
    
    private function parseSelectorString($selectorString, $separator)
    {
        $vertexList  = explode($separator, $selectorString);
        $vertexCount = count($vertexList);
        
        for ($i = 0; $i < $vertexCount; $i++) {
            if (preg_match('/^\d+$/', $vertexList[$i])) {
                $vertexList[$i] = (int) $vertexList[$i];
            }
        }
        
        return $vertexList;
    }
}