<?php
/**
 * Choice module
 * 
 * PHP version 5
 * 
 * @category  IrcChoice
 * @package   Modules
 * @author    Alexander Hackersson <waixan@waixan.se>
 * @copyright 1970 - 2012
 * @license   http://en.wikipedia.org/wiki/BSD_licenses#2-clause_license_.28.22Simplified_BSD_License.22_or_.22FreeBSD_License.22.29 2 clause BSD license
 * @link      http://pear.php.net/package/PackageName
 *
 */

/**
 * Choice module
 * 
 * PHP version 5
 * 
 * @category  IrcChoice
 * @package   Modules
 * @author    Alexander Hackersson <waixan@waixan.se>
 * @copyright 1970 - 2012
 * @license   http://en.wikipedia.org/wiki/BSD_licenses#2-clause_license_.28.22Simplified_BSD_License.22_or_.22FreeBSD_License.22.29 2 clause BSD license
 * @link      http://pear.php.net/package/PackageName     
 *
 */
class Choice implements Module
{
    /** 
     * Unique id for this instance.
     * @var string
     */
    protected $id;

    /**
     * Holds parent object
     * @var object
     */
    protected $parent;
    
    /**
     * Holds database object
     * @var object
     */
    protected $db;

    /**
     * The constructor
     *
     * @param object $parent The calling object
     * @param array  $params An array of parameters
     */
    public function __construct($parent, $params) 
    {
        $this->id = uniqid();
        $this->parent = $parent;
        
        /** Someone with more regex skills please enhance the regex match */      
        $parent->attach(
            'bd_'.$this->id, 
            Array($this,'prepareChoice'), 
            Array('PRIVMSG'), '/^(!choice)\s?(.+)?/i'
        );
		
		$parent->attach(
            'bd2_'.$this->id, 
            Array($this,'prepareChoice'), 
            Array('PRIVMSG'), '/^(!8ball)\s?(.+)?/i'
        );
    }

    /**
     * Prepares choices and send them to function getAnswer
     *
     * @param object $parent The object that's calling the module
     * @param array  $data   Data
     * @param array  $extra  Extra data such as regexps
     *
     * @return none
     */
    public function prepareChoice($parent, $data, $extra)
    {
        extract($extra);
        if ($regexp['0'] == '!choice') {
            $output = $nick.': need more choices. Atleast 2.';
        } elseif ($regexp['0'] == '!8ball') {
            $output = $nick.': need more choices. Atleast 2.';
        } else {
            $output = sprintf('%s: %s', $nick, $this->getAnswer($regexp['2']));
            if ($output == false) {
                $output = $nick.': need more choices. Atleast 2.';    
            }
        }
        $this->parent->privmsg($replyto, $output);
    }
    
    /**
     * Handler for all the functions used for the answer 
     * 
     * @param string $input Input with whole choice string
     * 
     * @return string Random answer
     */
    protected function getAnswer($input)
    {
        // Send input via getChoicesArray function.
        $ChoiceArray = $this->getChoicesArray($input);
        // Get random answer via getRandomChoice function.
        $answer = $this->getRandomChoice($ChoiceArray);
        return $answer;
    }
    
    /**
     * Function to get the choices in an array
     * 
     * @param string $string String containing all choices
     * 
     * @return array Returns an array with all choices
     */
    protected function getChoicesArray($string)
    {
        $ChoiceArray = Array();
        $string = str_split($string);
        $inquotation = false;
        $choice = null;
        foreach ($string as $char) {
            // If character is ", go through if-statement.
            if ($char == '"') {
                // If not inquotation, set it to true.
                if (!$inquotation) {
                    $inquotation = true;
                } else {
                    // End of quote, set the inquotation to false again.
                    $inquotation = false;
                    // Push the choice to the array.
                    array_push($ChoiceArray, $choice);
                    // choice variable needs to be reset before running again.
                    $choice = null;
                }
                // Detect spaces from character.
            } elseif (preg_match('/\s/i', $char)) {
                // Check if we are in quotation-mode.
                if ($inquotation) {
                    $choice .= $char;
                } else {
                    // If we aren't in quotation-mode
                    // Add to $ChoiceArray if the choice is more 
                    // than 1 character long.
                    if (strlen($choice) >= 1) {
                        array_push($ChoiceArray, $choice);
                        $choice = null;
                    }
                }
            } else {
                // If no error appeared, add to choice.
                $choice .= $char;
            }
        }
        return $ChoiceArray;
    }

    /**
     * Generates random choice from an array containing all the choices 
     * 
     * @param array $Choices Array containing
     *  
     * @return string or bootlean, Returns either a string containing random
     * choice as string or a bootlean false if elements count is higher 
     * or equal to 2 
     */
    protected function getRandomChoice($Choices)
    {
        // Count the amount of choices available 
        $elements = count($Choices);
        // If there is above two choices proceed and return a random choice.
        // If there is below two choices, return false.
        if ($elements >= 2) {
            // Return a random choice.
            return $Choices[mt_rand(0, $elements-1)];
        } else {
            // Return false, let prepareChoice() handle it.
            return false;
        }
    }
}