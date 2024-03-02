<?php
 
if(!defined('DOKU_INC')) die();

if(class_exists("helper_plugin_redirect", TRUE)) {

    class action_plugin_fixredirectlinks extends DokuWiki_Action_Plugin { 
    
        /**
         * Register its handlers with the DokuWiki's event controller
         */
        public function register(Doku_Event_Handler $controller) {

            $controller->register_hook('RENDERER_CONTENT_POSTPROCESS', 'AFTER', $this, 'fix_redirect_links');
        
        }

        /**
         * @param Doku_Event $event  event object by reference
         * @param mixed      $param  empty
         * @param string     $advise the advise the hook receives
         */
        public function fix_redirect_links (&$event, $param=null) {
            global $INFO;

            $redirHelper = new helper_plugin_redirect();

            libxml_use_internal_errors(TRUE);
            $domDoc = new DOMDocument('1.0', 'UTF-8');
            $domDoc->loadHTML(mb_convert_encoding($event->data[1], 'HTML-ENTITIES', "UTF-8"), LIBXML_HTML_NODEFDTD);
            
            $aTags = $domDoc->getElementsByTagName("a");
            $nb = $aTags->length;
            for($pos=0; $pos<$nb; $pos++) {
                $node = $aTags[$pos];
                $classes = $node->getAttribute("class");
                if(preg_match('/wikilink2/i', $classes)){
                    $title = $node->getAttribute("title");

                    $redirects = $redirHelper->getRedirectURL($title);

                    if($redirects) {
                        $url = parse_url($redirects);
                        $id = "";
                        if($url["query"]) {
                            $query = parse_str($url["query"]);
                            if($query["id"]) $id = $query["id"];
                        }
                        if($url["path"]) {
                            $path = $url["path"];
                            $dokuRel =  trim(DOKU_REL, "/");
                            $cleanPath = ltrim(preg_replace(["/$dokuRel/i", '/\/doku.php/i'], ["", ""], $path), "/");
                            if($cleanPath) {
                                $cleanPath = str_replace("/", ":", $cleanPath);
                                $id = $cleanPath;
                            }
                        }
                        if(page_exists($id)) {
                            $newClasses = preg_replace('/wikilink2/i', "wikilink1", $classes);
                            $node->removeAttribute('class');
                        
                            if($id == $INFO['id']) {
                                $newClasses .= " active";
                            }
                            $node->setAttribute("class", $newClasses);
                        }
                    }
                    
                }
            }
            $body = $domDoc->getElementsByTagName('body')->item(0);
            $event->data[1] = "";
            foreach ($body->childNodes as $childNode) {
                $event->data[1] .= $domDoc->saveHTML($childNode);
            }
        }
    }
}