<?php
/**
 * Anime module
 *
 * PHP version 5
 *
 * @category  IrcAnime
 * @package   Modules
 * @author    Joakim Nylén <me@jnylen.nu>
 * @copyright 2014-
 * @license   http://en.wikipedia.org/wiki/BSD_licenses#3-clause_license_.28.22Revised_BSD_License.22.2C_.22New_BSD_License.22.2C_or_.22Modified_BSD_License.22.29 3 clause BSD license
 * @link      http://pear.php.net/package/PackageName
 *
 */


class Anime implements Module
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
     * Holds database object
     * @var object
     */
    protected $db;


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
				
                $parent->attach('Anime_'.$this->id, Array($this,'search'), Array('PRIVMSG'), '/^!anime (?P<search>.*)$/i');
                $parent->attach('Anidb_'.$this->id, Array($this,'anidb'), Array('PRIVMSG'), '/^!anidb (?P<id>.*)$/i');
        }
		
		public function search($parent, $data, $extra)
        {
                extract($extra);
				
				$db = mysql_connect(
					CONFIG_mysql_server, 
					CONFIG_mysql_username, 
					CONFIG_mysql_password
				);
				
				mysql_select_db(CONFIG_mysql_database, $db);
				
				$title = mysql_real_escape_string($regexp['search']);
				if (!$q = mysql_query(
					sprintf(
						"SELECT `aid` FROM `animetitles`".
						" WHERE `title` = '%s' LIMIT 1",
						$title
					)
				)) {
					//$this->parent->privmsg($replyto, 'Something weird happened.');
				} 
				
				var_dump($q);
				
				if (mysql_num_rows($q) > 0) {
					$row = mysql_fetch_assoc($q);
					$this->parent->privmsg($replyto, $this->anidb_result($row['aid']));
				} else {
					$this->parent->privmsg($replyto, 'No results found.');
				}
				
				mysql_close($db);
		}
		
		public function anidb($parent, $data, $extra)
        {
                extract($extra);
				$this->parent->privmsg($replyto, $this->anidb_result($regexp['id']));
		}
	
function anidb_result($anidbID)
{
    $ch = curl_init('http://api.anidb.net:9001/httpapi?request=anime&client=inorii&clientver=1&protover=1&aid='.$anidbID);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_FAILONERROR, 1);
    curl_setopt($ch, CURLOPT_ENCODING, 'gzip');

    $apiresponse = curl_exec($ch);
    if(!$apiresponse)
        return false;
    curl_close($ch);

    //TODO: SimpleXML - maybe not.

    $AniDBAPIArray['anidbID'] = $anidbID;

    preg_match_all('/<title xml:lang="x-jat" type="(?:official|main)">(.+)<\/title>/i', $apiresponse, $title);
    $AniDBAPIArray['title'] = isset($title[1][0]) ? $title[1][0] : '';

    preg_match_all('/<(type|(?:start|end)date)>(.+)<\/\1>/i', $apiresponse, $type_startenddate);
    $AniDBAPIArray['type'] = isset($type_startenddate[2][0]) ? $type_startenddate[2][0] : '';
    $AniDBAPIArray['startdate'] = isset($type_startenddate[2][1]) ? $type_startenddate[2][1] : '';
    $AniDBAPIArray['enddate'] = isset($type_startenddate[2][2]) ? $type_startenddate[2][2] : '';

    preg_match_all('/<anime id="\d+" type=".+">([^<]+)<\/anime>/is', $apiresponse, $related);
    $AniDBAPIArray['related'] = isset($related[1]) ? implode($related[1], '|') : '';

    preg_match_all('/<name id="\d+" type=".+">([^<]+)<\/name>/is', $apiresponse, $creators);
    $AniDBAPIArray['creators'] = isset($creators[1]) ? implode($creators[1], '|') : '';

    preg_match('/<description>([^<]+)<\/description>/is', $apiresponse, $description);
    $AniDBAPIArray['description'] = isset($description[1]) ? $description[1] : '';

    preg_match('/<permanent count="\d+">(.+)<\/permanent>/i', $apiresponse, $rating);
    $AniDBAPIArray['rating'] = isset($rating[1]) ? $rating[1] : '';

    preg_match('/<picture>(.+)<\/picture>/i', $apiresponse, $picture);
    $AniDBAPIArray['picture'] = isset($picture[1]) ? $picture[1] : '';

    preg_match('/<episodecount>(.+)<\/episodecount>/i', $apiresponse, $episodecount);
    $AniDBAPIArray['episodecount'] = isset($episodecount[1]) ? $episodecount[1] : '';

    if($AniDBAPIArray['episodecount'] == "" || $AniDBAPIArray['episodecount'] < 1) {
        $AniDBAPIArray['episodecount'] = "?";
    }

    preg_match_all('/<category id="\d+" parentid="\d+" hentai="(?:true|false)" weight="\d+">\s+<name>([^<]+)<\/name>/is', $apiresponse, $categories);
    $AniDBAPIArray['categories'] = isset($categories[1]) ? implode($categories[1], '|') : '';

    preg_match_all('/<resource type="2">\s+<externalentity>\s+<identifier>([^<]+)<\/identifier>/is', $apiresponse, $mal_id);
    $AniDBAPIArray['mal_id'] = isset($mal_id[1]) ? end($mal_id[1]) : '';

    if($AniDBAPIArray['mal_id'] != "")
    {
        $url = "http://myanimelist.net/anime/".$AniDBAPIArray['mal_id'];
    } else {
        $url = 'http://anidb.net/perl-bin/animedb.pl?show=anime&aid='.$anidbID;
    }

    preg_match_all('/<character id="\d+" type=".+" update="\d{4}-\d{2}-\d{2}">\s+<name>([^<]+)<\/name>/is', $apiresponse, $characters);
    $AniDBAPIArray['characters'] = isset($characters[1]) ? implode($characters[1], '|') : '';

    preg_match('/<episodes>\s+<episode.+<\/episodes>/is', $apiresponse, $episodes);
    preg_match_all('/<epno>(.+)<\/epno>/i', $episodes[0], $epnos);
    $AniDBAPIArray['epnos'] = isset($epnos[1]) ? implode($epnos[1], '|') : '';
    preg_match_all('/<airdate>(.+)<\/airdate>/i', $episodes[0], $airdates);
    $AniDBAPIArray['airdates'] = isset($airdates[1]) ? implode($airdates[1], '|') : '';
    preg_match_all('/<title xml:lang="en">(.+)<\/title>/i', $episodes[0], $episodetitles);
    $AniDBAPIArray['episodetitles'] = isset($episodetitles[1]) ? implode($episodetitles[1], '|') : '';

    // Ended?
    if($AniDBAPIArray['enddate'] != "")
    {
        $ended .= " til' ".$AniDBAPIArray['enddate'];
    } else {
        $ended = "";
    }

    $output = '"'.$AniDBAPIArray['title'].'" - a '.$AniDBAPIArray['type']. ' with rating '.$AniDBAPIArray['rating'].'. Air(s/ed) '.$AniDBAPIArray['startdate'].$ended.' and have '.$AniDBAPIArray['episodecount'].' episodes. Read more at '.$url;

    return $output;
}
		

}
