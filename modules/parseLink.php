<?php

class parseLink implements Module {
	private $id;
	private $parent;

	public function __construct($parent, $params) {
		$this->id = uniqid();
		$parent->attach('parseLink_'.$this->id, Array($this,'privmsg'), Array('PRIVMSG'));
		$this->parent = $parent;
	}

	public function privmsg($parent, $data, $extra) {
		extract($extra);

		if ($spotify = $this->spotify($msg)) {
			$http = new httpSession('ws.spotify.com');
			$http->setMaxLength(16384);
			$http->getPage(
				Array($this, 'html'),
				array_merge($extra, Array('type' => 'spotify', 'parent' => $parent)),
				'/lookup/1/?uri=spotify:'.$spotify[1].':'.$spotify[2]
			);
		} elseif ($url = $this->url($msg)) {
			$http = new httpSession($url[1], (strtolower($url[3])=='https'?443:80), strtolower($url[3])=='https');
            $http->setMaxLength(524288);
            if (strpos(strtolower($url[2]), '.gif') !== false)
    			$http->setMaxLength(16*1048576);
			$http->getPage(Array($this, 'html'), array_merge($extra, Array('type' => 'uri', 'parent' => $parent)), $url[2]);
            echo "privmsg(): getting page".PHP_EOL;
		}

	}

	protected function escape($title) {
		return str_replace(
			Array("\r","\n","\t","\0x00"), 
			"", 
			trim(
				$this->parent->charsetDecode(
					$title
				)
			)
		);
	}

	protected function spotify($msg) {
		$regex_types = "(track)"; //|album|artist)";
		$regex_id = "([0-9a-zA-Z]{22})";
		$regex_url = "http\:\/\/open.spotify.com\/$regex_types\/$regex_id";
		$regex_uri = "spotify\:$regex_types\:$regex_id";

		preg_match_all("/$regex_url/", $msg, $matches1, 2);
		preg_match_all("/$regex_uri/", $msg, $matches2, 2);

		$media = array_merge($matches1, $matches2);

		$r = '';

		foreach($media as $m) {
			return $m;
		}

		return false;

	}

	protected function url($msg) {
		if (preg_match('~\b(https?)://((?:[^/\s]+\\.)+(?:[a-z]{2,10}))(/[^\s]+)?\.?(\s|$)~i', $msg, $match) == 1) {
			if (empty($match[2]))
			$match[2] = '/';
			return Array($match[0], $match[2], $match[3], $match[1]);
		}
		return false;
	}
	
	public function html($http, $page, $vars) {
		extract($vars);
		
        echo "html(): {$page->url}" . PHP_EOL;

		switch($type) {
			case 'uri':
				if ($page->status == 200) {
					if (preg_match('~<title>(.*?)</title>~is', $page->data, $out) == 1) {
						$title = str_replace(Array("\r", "\n", "\t"), "", trim($out[1]));
						if (strlen($title) > 150)
							$title = substr($title, 0, 150) . '...';
							
						$parent->privmsg($replyto, (isset($httphost)?'['.$httphost.'] ':'').sprintf("Link title: %s", html_entity_decode($title)));
					} else {
                        $extra = '';
                        if (isset($page->header['content-length'])) {
                            $bytes = $page->header['content-length'];
                            $prefix = Array('','k','M','G','T','P');
                            $i = 0;
                            while ($bytes > 1024 && ++$i < count($prefix))
                                $bytes /= 1024;

                            $extra .= ', '.round($bytes,2).$prefix[$i].'B';

                        }
                        if ($page->header['content-type'] == 'image/gif' && strlen($page->data) < 16*1048576) {
                            if (false !== ($pos=strpos($page->data, 'GIF89a'))) {
                                list (,$width,$height) = unpack('v*', substr($page->data, $pos+6, 4));
                                $extra .= ", {$width}x{$height}px";
                            }
                            if (false !== strpos($page->data, 'NETSCAPE2.0')) {
                                $count = preg_match_all('/\x00\x21\xF9\x04.(..).\x00/s', $page->data, $out);
                                if ($count > 0) {
                                    $total_delay = 0;
                                    foreach($out[1] as $delay) {
                                        list(,$delay) = unpack('v', $delay);
                                        $total_delay += $delay;
                                    }
                                    $extra .= ', animated ('.$count.' frames, '.($total_delay/100).' seconds)';
                                }

                            }

                        }
                        $parent->privmsg($replyto, (isset($httphost)?'['.$httphost.'] ':'').sprintf("[%s] %s", $page->header['content-type'], ltrim($extra,', ')));
					}
				} elseif (isset($page->header['location'])) {
                    if (isset($redir) && $redir>2) return;
		            if ($url = $this->url($page->header['location'])) {
            			$http = new httpSession($url[1]);
            			$http->setMaxLength(16384);
                        echo "html(): http redirect to {$page->header['location']}".PHP_EOL;
            			$http->getPage(Array($this, 'html'), array_merge($vars, Array(
                            'redir' => (isset($redir)?$redir+1:1), 
                            'httphost' => $url[1], 
                            'type' => 'uri', 
                            'parent' => $parent
                        )), $url[2]);
            		}
				} else {
                    echo "html(): Got {$page->status}".PHP_EOL;
                }
				break;
				;;
				
			case 'spotify':
				if ($page->status == 200) {
					$spotify = simplexml_load_string($page->data);

					$r = sprintf('%s - %s', 
						$parent->charsetDecode($spotify->artist->name),
						$parent->charsetDecode($spotify->name)
					);
					if (isset($spotify->album))
						$r .= ' / ' . $parent->charsetDecode($spotify->album->name);
					
					$len_m = floor($spotify->length / 60);
					$len_s = floor($spotify->length % 60);
					
					$r .= sprintf(' (%d:%02d)', $len_m, $len_s);
					
					$parent->privmsg($replyto, sprintf("[spotify] $r"));
				}
				break;
				;;
		}
		
	}
}
