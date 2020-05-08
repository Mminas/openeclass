<?php

trait paramsTrait
{

    private function _checkParams(array $params, array $incoming)
    {
        $validparams = array();

        // check required first
        if ( array_key_exists('required',$params) ) {
            foreach ($params['required'] as $pk => $pn) {
                if (is_numeric($pk)) {
                    $x = explode(':', $pn);
                    $incomingidx = $x[0];
                } else {
                    $x = explode(':', $pk);
                    $incomingidx = $pn;
                }
                $name = $x[0];
                $p = $this->_requiredParam($incomingidx, $incoming);
                if (count($x) > 1) {
                    if (! $this->_checkParamType($p, $x[1])) {
                        throw new Exception('Invalid parameter type for ' . $name . var_export($incoming, true));
                    }
                }
    
                $validparams[$name] = $p;
            }
        }

        // now check optionals and remove blank ones
        if ( array_key_exists('optional',$params) ) {
            foreach ($params['optional'] as $pk => $pn) {
                
                if (is_numeric($pk)) {
                    $x = explode(':', $pn);
                    $incomingidx = $x[0];
                } else {
                    $x = explode(':', $pk);
                    $incomingidx = $pn;
                }
                $name = $x[0];
                if (! array_key_exists($incomingidx, $incoming)) {
                    continue;
                }
                $p = $incoming[$incomingidx];
                if ( is_string($p) && trim($p) === '')
                    continue; // next param. Skip this one, it's blank
                if (count($x) > 1) {
                    if (! $this->_checkParamType($p, $x[1])) {
                        throw new Exception('Invalid parameter type for ' . $name . '! Expected '.$x[1].'! ' . var_export($incoming, true));
                    }
                }
                $validparams[$name] = $p;
            }
        }

        return $validparams;
    }

    private function _checkParamType($value, $typespec)
    {
        if (substr($typespec, 0, 4) == 'enum') {
            $options = explode('|', substr($typespec, 5, strlen($typespec) - 6));
            if (count($options) < 2)
                die('Invalid typespec ' . $typespec);
            return in_array($value, $options, true);
        } elseif ($typespec === 'number' ) {
            return is_numeric($value);
        } elseif ($typespec === 'integer' ) {
            return is_integer($value);
        } elseif ($typespec === 'bool') {
            return is_bool($value);
        } elseif ($typespec === 'boolstr') {
            return $value === 'true' || $value === 'false';
        }
    }

    /**
     *
     * @brief Explodes if param is empty or paramArray[param] is empty
     * @param string $param
     * @param array|null $paramArray
     * @throws Exception
     * @return string
     */
    private function _requiredParam($param, $paramArray = null)
    {
        if (is_array($paramArray)) {
            if (array_key_exists($param, $paramArray))
                return $paramArray[$param];
            else
                throw new Exception('Missing parameter ' . $param . var_export($paramArray, true));
        }

        if (! isset($param) || $param == '') {
            throw new Exception('Missing parameter '.$param.'!');
        } else
            return $param;
    }

    /**
     *
     * @brief Concatenates given required parameters (use when at least one must be non-blank)
     * @param array $params
     * @param array $paramArray
     * @return string|boolean
     */
    private function _requiredParams(array $params, array $paramArray)
    {
        if (count($params) > 0 && count($paramArray) > 0) {
            $x = '';
            foreach ($params as $i) {
                if (array_key_exists($i, $paramArray))
                    $x .= ' ' . $paramArray[$i];
            }
            return trim($x);
        }
        throw new Exception('Missing parameters ' . implode(',', $params) . var_export($paramArray, true));
    }

    /* Pass most optional params through as set value, or set to '' */
    /* Don't know if we'll use this one, but let's build it in case. */
    /*
     * private function _optionalParam($param) {
     * if ((isset($param)) && ($param != '')) {
     * return $param;
     * } else {
     * $param = '';
     * return $param;
     * }
     * }
     */
}