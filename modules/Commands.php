<?php

/**
 * Handles IRC Commands actions for neotor.
 *
 * PHP version 5
 *
 * @category  IrcCommands
 * @package   Modules
 * @author    oldmagic oldmagic <oldmagic@zenet.org> (imdb-function)
 * @author    Joakim Nylén <me@jnylen.nu>
 * @copyright 2012 oldmagic, 2014 Joakim Nylén
 * @license   http://www.php.net/license/3_01.txt  PHP License 3.01
 * @link      http://pear.php.net/package/PackageName
 */

class Commands implements Module
{

    /**
     * The unique id of this module
     *
     * @var string
     */

        protected $id;

    /**
     * The caller of this module
     *
     * @var object
     * @access protected
     */

        protected $parent;

    /**
     * The constructor
     *
     * @param object $parent The calling object.
     * @param array  $params Parameters for the __construct()
     */

        public function __construct($parent, $params)
        {
                $this->id = uniqid();
                $this->parent = $parent;

                $parent->attach('Imdb_'.$this->id, Array($this,'imdb'), Array('PRIVMSG'), '/^!i (?P<search>.*)$/i');
		$parent->attach('Help_'.$this->id, Array($this,'help'), Array('PRIVMSG'), '/^!help$/i');
		$parent->attach('Nyan_'.$this->id, Array($this,'nyan'), Array('PRIVMSG'), '/^!nyan$/i');
		$parent->attach('Nyan2_'.$this->id, Array($this,'nyan'), Array('PRIVMSG'), '/nyan/i');
		$parent->attach('Puru_'.$this->id, Array($this,'puru'), Array('PRIVMSG'), '/puru/i');
		$parent->attach('Meru_'.$this->id, Array($this,'meru'), Array('PRIVMSG'), '/meru/i');
		$parent->attach('Pipiru_'.$this->id, Array($this,'pipiru'), Array('PRIVMSG'), '/pipiru/i');
        }
		
	public function help($parent, $data, $extra)
	{
		extract($extra);
		$this->parent->privmsg($replyto, "Available commands: !anime <search> - !manga <type (not needed)> <search> - !i <movie name> - !animetop - !mangatop - nyan - puru - meru - !bd <nick> - !bdset <YYYYMMDD> - !bdstats");
	}
	
	public function puru($parent, $data, $extra)
	{
		extract($extra);
		$this->parent->privmsg($replyto, "Puru Puru Pururin Puru Puru Pururin Puru Puru Pururin");
	}
	
	public function meru($parent, $data, $extra)
	{
		extract($extra);
		$this->parent->privmsg($replyto, "MERU MERU MERU MERU MERU MERU ME~");
	}
	
	public function pipiru($parent, $data, $extra)
	{
		extract($extra);
		$this->parent->privmsg($replyto, "Pipiru piru piru pipiru pi");
	}
	
	public function nyan($parent, $data, $extra)
	{
		extract($extra);
		
		$url = "http://thecatapi.com/api/images/get";
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Must be set to true so that PHP follows any "Location:" header
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$a = curl_exec($ch); // $a will contain all headers

		$url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL); // This is what you need, it will return you the last effective URL

	
		$this->parent->privmsg($replyto, "Nyan Nyan Nyan Nyan Ni Hao Nyaaan~ ".$url);
	}

    /**
     * IMDB Handler
     *
     * @param object $parent The object that's calling the module
     * @param array  $data   Data
     * @param array  $extra  Extra data such as regexps
     *
     * @return none
     */

        public function imdb($parent, $data, $extra)
        {
                extract($extra);
                $http = new httpSession('omdbapi.com');
                $http->getPage(
                Array($this,'imdbResult'),
                $extra,
                '/?t=' . urlencode($regexp['search'])
        );
	}

    /**
     * Prints the imdb results
     *
     * @param object $http Object with http data.
     * @param object $page Object with the return data.
     * @param array  $extra Array with extra stuff
     *
     * @return none
     */

    public function imdbResult($http, $page, $extra)
    {
        extract($extra);
        if ($page->status != 200) return;
        $results = json_decode($page->data);
        $imdbid = $results->imdbID;
        $title = $results->Title;
        $year = $results->Year;
        $released = $results->Released;
        $runtime = $results->Runtime;
        $genre = $results->Genre;
        $rating = $results->imdbRating;
		
		$year = str_replace("â", "-", $year);

		$this->parent->privmsg($replyto, "\"$title\" - From $year with a rating of $rating/10. Has a runtime of $runtime. Read more at http://www.imdb.com/title/$imdbid/");
    }


}
