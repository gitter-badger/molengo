<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Molengo;

class Object
{

    protected $arrEvent = array();

    /**
     * Register event callback
     *
     * @param type $strEvent
     * @param type $callback
     */
    protected function on($strEvent, $callback)
    {
        $this->arrEvents[$strEvent][] = $callback;
    }

    /**
     * Trigger event
     *
     * @param string $strEvent
     * @param array $arrParams
     * @return boolean
     */
    protected function trigger($strEvent, array $arrParams = null)
    {
        $boolReturn = false;
        if (!empty($this->arrEvents[$strEvent])) {
            foreach ($this->arrEvents[$strEvent] as $event) {
                $boolReturn = $event[0]->$event[1]($arrParams);
                if ($boolReturn === false) {
                    return false;
                }
            }
        }
        return $boolReturn;
    }

    /**
     * Call a PHP class function with optional parameters and return result
     *
     * @param object $controller
     * @param string $strAction e.g. ClassName.echo
     * @param array $arrParams parameter for function
     * @return mixed return value from function
     * @throws \Exception
     */
    protected function call($controller, $strAction, $arrParams = null)
    {
        $arrResult = null;
        //$strMethod = substr(strrchr($strAction, "."), 1);

        $arrCallbackParams = array('method' => $strAction);
        if (!$this->trigger('beforeCall', $arrCallbackParams)) {
            throw new Exception('Permission denied', 403);
        }

        // check if function exist
        if (!method_exists($controller, $strAction)) {
            throw new \Exception("Action '$strAction' not found");
        }

        // call function
        if ($arrParams === null) {
            $arrResult = $controller->{$strAction}();
        } else {
            $arrResult = $controller->{$strAction}($arrParams);
        }
        return $arrResult;
    }
}
