<?php

namespace Koochik\QueryHall\Parsers;

use Koochik\QueryHall\Interfaces\ParameterParser;

class PSRArrayNotationParser implements ParameterParser
{

    private function getParameters($request)
    {
        return $request->getQueryParams();
    }

    private function parsString($queryString): array
    {
        // Trim whitespace from the input string
        $queryString = trim($queryString);

        // Check if the string starts and ends with square brackets
        if (str_starts_with($queryString, '[') && str_ends_with($queryString, ']')) {
            // It's an array string, parse it
            $array = [];
            $currentVal = '';
            $insideQuotes = false;

            // Remove leading and trailing square brackets
            $queryString = trim($queryString, '[]');

            for ($i = 0; $i < strlen($queryString); $i++) {
                $char = $queryString[$i];

                if ($char == "'") {
                    $insideQuotes = !$insideQuotes;
                } elseif ($char == '\\' && $insideQuotes) {
                    // Handle escaped characters
                    $i++;
                    if ($i < strlen($queryString)) {
                        $currentVal .= $queryString[$i];
                    } else {
                        // Malformed input, trailing backslash
                        return [$queryString];
                    }
                } elseif ($char == ',' && !$insideQuotes) {
                    $array[] = $currentVal;
                    $currentVal = '';
                } else {
                    $currentVal .= $char;
                }
            }

            // Add the last value
            if (!empty($currentVal)) {
                $array[] = $currentVal;
            }

            return $array;
        } elseif (!str_contains($queryString, '[') && !str_contains($queryString, ']')) {
            // It's a single value, return it as an array
            return [$queryString];
        }

           throw throw new \InvalidArgumentException();
    }


    public function pars($rawParameters): array
    {
        if (is_array($rawParameters)) {
            return $rawParameters;
        }

        $parameters = $this->getParameters($rawParameters);

        $parsedParameters = [];

        foreach ($parameters as $key => $value) {
            // Parse each parameter string

            try {
                $parsedParameters[$key] = $this->parsString($value);

            } catch(\InvalidArgumentException $e){


            }

        }

        return $parsedParameters;
    }
}
