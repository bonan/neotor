<?php
/**
 * Anime2 module
 *
 * PHP version 5
 *
 * @category  IrcAnime2
 * @package   Modules
 * @author    Joakim Nylén <me@jnylen.nu>
 * @copyright 2014-
 * @license   http://en.wikipedia.org/wiki/BSD_licenses#3-clause_license_.28.22Revised_BSD_License.22.2C_.22New_BSD_License.22.2C_or_.22Modified_BSD_License.22.29 3 clause BSD license
 * @link      http://pear.php.net/package/PackageName
 *
 */


class Anime2 implements Module
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
				
				
                $parent->attach('Anime_'.$this->id, Array($this,'anime'), 	  		  Array('PRIVMSG'), '/^!anime (?P<search>.*)$/i');
				$parent->attach('Anime2_'.$this->id, Array($this,'anime'), 	  		  Array('PRIVMSG'), '/^!a (?P<search>.*)$/i');
				$parent->attach('Manga_'.$this->id, Array($this,'mangatype'), 		  Array('PRIVMSG'), '/^!manga (?P<search>.*)$/i');
				$parent->attach('Manga2_'.$this->id, Array($this,'mangatype'), 		  Array('PRIVMSG'), '/^!m (?P<search>.*)$/i');
				
				$parent->attach('AnimeTop_'.$this->id, Array($this,'animetop'), 	  Array('PRIVMSG'), '/^!animetop$/i');
				$parent->attach('MangaTop_'.$this->id, Array($this,'mangatop'), 	  Array('PRIVMSG'), '/^!mangatop$/i');
        }
		
		/** Need, gzip u kno **/
		function gzip_decode($data){
			$g=tempnam('/tmp','ff');
			@file_put_contents($g,$data);
			ob_start();
			readgzfile($g);
			$d=ob_get_clean();
			return $d;
		}
		
		/** MAL **/
		
		function malapi_request($path = FALSE)
		{
			$ch = curl_init();
			curl_setopt( $ch, CURLOPT_URL, "http://newapi.atarashiiapp.com/".$path); // https://github.com/AnimaSA/Atarashii/
			curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt( $ch, CURLOPT_HEADER, 0);
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt( $ch, CURLOPT_TIMEOUT, 10);
			
			// Data returned 
			$response = json_decode($this->gzip_decode(curl_exec($ch)));
			return $response;
		}
		
		public function animetop($parent, $data, $extra)
        {
                extract($extra);
				
				$data = $this->malapi_request("anime/top");
				
				// Data
				if(count($data) > 0)
				{
					// Max 5.
					$count = 0;
					
					$output = "MAL-Toplist for Anime: ";
					
					foreach($data as $d)
					{
						if($count > 4) { continue; }
						$count = $count+1; // add one each foreach
						
						$output .= $count.". ".$d->title." (".$d->type.", ".$d->members_score."/10) ";
					}
					
					$this->parent->privmsg($replyto, $output);
				} else {
					$this->parent->privmsg($replyto, 'No results in response from API.');
				}
		}
		
		public function mangatop($parent, $data, $extra)
        {
                extract($extra);
				
				$data = $this->malapi_request("manga/top");
				
				// Data
				if(count($data) > 0)
				{
					// Max 5.
					$count = 0;
					
					$output = "MAL-Toplist for Manga: ";
					
					foreach($data as $d)
					{
						if($count > 4) { continue; }
						$count = $count+1; // add one each foreach
						
						$output .= $count.". ".$d->title." (".$d->members_score."/10) ";
					}
					
					$this->parent->privmsg($replyto, $output);
				} else {
					$this->parent->privmsg($replyto, 'No results in response from API.');
				}
		}
		
		public function anime($parent, $data, $extra)
        {
                extract($extra);
				
				$data = $this->malapi_request("anime/search?q=".urlencode($regexp['search']));
				
				// Data returned?
				if(isset($data->error) && isset($data->error->code))
				{
					$e = $data->error;
					$this->parent->privmsg($replyto, 'API Returned: '. $e->code .' '.$e->message);
				} elseif(count($data) > 0 && isset($data[0]))
				{
					$d = $data[0]; // only use the first response
					
					// No results anyway
					if(!isset($d->id)) {
						$this->parent->privmsg($replyto, 'No results found.');
					}
					
					if($d->type == "TV") { $d->type = "TV Series"; } // Make it prettieh
					
					// Make it prettieh
					$aired = '';
					
					// End time set?
					if(isset($d->status) && $d->status != "" && $d->start_date == "" && $d->status == "not yet aired" && $d->end_date == "") {
						$aired = "Starts Not available";
					} elseif(isset($d->status) && $d->status != "" && $d->status == "not yet aired") {
						$aired = "Starts ".date("Y-m-d", strtotime($d->start_date));
					} elseif(isset($d->end_date) && $d->end_date != "" && $d->end_date != "-" && date("Y-m-d", strtotime($d->start_date)) == date("Y-m-d", strtotime($d->end_date))) {
						$aired = "Aired ".date("Y-m-d", strtotime($d->start_date));
					} elseif(isset($d->end_date) && $d->end_date != "" && $d->end_date != "-" && (strtotime(date("Y-m-d", strtotime($d->end_date))." 01:00") > strtotime(date("Y-m-d")." 01:00"))) {
						$aired = "Airs between ".date("Y-m-d", strtotime($d->start_date))." and ".date("Y-m-d", strtotime($d->end_date));
					} elseif(isset($d->end_date) && $d->end_date != "" && $d->end_date != "-" && (strtotime(date("Y-m-d", strtotime($d->end_date))." 01:00") < strtotime(date("Y-m-d")." 01:00"))) {
						$aired = "Aired between ".date("Y-m-d", strtotime($d->start_date))." and ".date("Y-m-d", strtotime($d->end_date));
					} elseif(strtotime(date("Y-m-d")." 01:00") > strtotime($d->start_date." 01:00")) {
						$aired = "Aired ".date("Y-m-d", strtotime($d->start_date));
					} else {
						$aired = "Airs ".date("Y-m-d", strtotime($d->start_date));
					}
					
					// Looks ugly otherwise
					if($d->episodes > 1) {
						$episodes = "s";
					} else {
						$episodes = "";
					}
					
					// pretty
					if(!$d->episodes) { $d->episodes = "Unknown"; $episodes = "s"; }
					if(!$d->members_score) { $d->members_score = "-"; }
					
					
					$output = '"'.$d->title.'" - a '.$d->type. ' with a rating of '.$d->members_score.'/10. '.$aired.' and has '.$d->episodes.' episode'.$episodes.'. Read more at http://myanimelist.net/anime/'.$d->id;
					
					$this->parent->privmsg($replyto, $output);
				} else {
					$this->parent->privmsg($replyto, 'No results found.');
				}
		}
		
		public function manga($extra, $text, $type = FALSE)
		{
			extract($extra);
		
			$data = $this->malapi_request("manga/search?q=".urlencode($text));
				
			// Data returned?
			if(isset($data->error) && isset($data->error->code))
			{
				$e = $data->error;
				$this->parent->privmsg($replyto, 'API Returned: '. $e->code .' '.$e->message);
			} elseif(count($data) > 0)
			{
				// different types
				$found = 0;
				foreach($data as $d)
				{
					if($found) { continue; }
					
					// No response.
					if(!isset($d->id)) {
						$this->parent->privmsg($replyto, 'No results found. Correct type? Types available: Manga, Novel, Doujin, Manwha, Manhua, OEL, OneShot');
						$found = 1;
						continue;
					}
					
					// Only this type
					if($type != FALSE) {
						$type = ucfirst(strtolower($type));
						if($type == "Oel") { $type = "OEL"; }
						if($type == "One shot") { $type = "One Shot"; }
						
						if($d->type != $type) {
							continue;
						}
					}
					
					$found = 1; // Found!
					
					// Make them prettieh
					if(!$d->volumes)  { $d->volumes = "Unknown"; }
					if(!$d->chapters) { $d->chapters = "Unknown"; }
					
					
					$output = '"'.$d->title.'" - a '.$d->type. ' with a rating of '.$d->members_score.'/10 and have '.$d->volumes.' volumes and '.$d->chapters.' chapters. Read more at http://myanimelist.net/manga/'.$d->id;
					
					$this->parent->privmsg($replyto, $output);
				}
					
				// No found
				if(!$found) {
					$this->parent->privmsg($replyto, 'No results found. Correct type? Types available: Manga, Novel, Doujin, Manwha, Manhua, OEL, OneShot');
				}
			} else {
				$this->parent->privmsg($replyto, 'No results found.');
			}
		}
		
		public function mangatype($parent, $data, $extra)
        {
                extract($extra);
				
				// Types
				list($match, $text) = explode(" ", $regexp['search'], 2);
				if ($match && !$text) {
					$this->manga($extra, $regexp['search']);
				}
				
				// Type
				if ($match && $text) {
					switch ($match) {
						case 'manga':
							$this->manga($extra, $text, 'manga');
							break;
						case 'novel':
							$this->manga($extra, $text, 'novel');
							break;
						case 'oneshot':
							$this->manga($extra, $text, 'one shot');
							break;
						case 'doujin':
							$this->manga($extra, $text, 'doujin');
							break;
						case 'manwha':
							$this->manga($extra, $text, 'manwha');
							break;
						case 'manhua':
							$this->manga($extra, $text, 'manhua');
							break;
						case 'oel':
							$this->manga($extra, $text, 'oel');
							break;
						default:
							$this->manga($extra, $regexp['search']);
							break;
					}
				}
				
				
		}
		
		/** AniDB **/
		
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
			
			$url = 'http://anidb.net/perl-bin/animedb.pl?show=anime&aid='.$anidbID;

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
				$ended = " til' ".$AniDBAPIArray['enddate'];
			} else {
				$ended = "";
			}

			$output = '"'.$AniDBAPIArray['title'].'" - a '.$AniDBAPIArray['type']. ' with rating '.$AniDBAPIArray['rating'].'. Air(s/ed) '.$AniDBAPIArray['startdate'].$ended.' and have '.$AniDBAPIArray['episodecount'].' episodes. Read more at '.$url;

			return $output;
		}
		

}
