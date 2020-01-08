<?php
namespace JBBCode;

defined('PROJECT_PATH') OR exit('No direct script access allowed');

/**
 * An interface for sets of code definitons.
 *
 * @author jbowens
 */
interface CodeDefinitionSet
{

    /**
     * Retrieves the CodeDefinitions within this set as an array.
     */
    public function getCodeDefinitions();

}
