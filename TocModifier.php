<?php

namespace Statamic\Addons\Toc;

use Statamic\Extend\Modifier;

class TocModifier extends Modifier
{
    /**
     * Modify a value
     *
     * @param mixed  $value    The value to be modified
     * @param array  $params   Any parameters used in the modifier
     * @param array  $context  Contextual values
     * @return mixed
     */
    public function index($value, $params, $context) {
      $fully_cooked = $this->createToc($value);

      $toc = "<div class='markdown-toc'><div class='markdown-toc-title'>Contents</div><div class='markdown-toc-block'>".$fully_cooked['toc']."</div></div>";

      $modified = $fully_cooked['content'];

      if ($param = array_get($params, 0)) {
        $modified = $toc . $fully_cooked['content'];
      }

      return $modified;
  	}

    // This function contains the guts of the routine, and has been very
    // slightly modified from the original code by Joost de Valk
    //
    private function createToc($content)
    {
        preg_match_all( '/<h([1-3])(.*)>([^<]+)<\/h[1-3]>/i', $content, $matches, PREG_SET_ORDER );

        global $anchors;

        $anchors = array();
        $toc     = '<ol>'."\n";
        $i       = 0;

        foreach ( $matches as $heading ) {

            if ($i == 0)
            $startlvl = $heading[1];
            $lvl        = $heading[1];

            $ret = preg_match( '/id=[\'|"](.*)?[\'|"]/i', stripslashes($heading[2]), $anchor );


            if ( $ret && $anchor[1] != '' ) {
                $anchor = stripslashes( $anchor[1] );
                $add_id = false;
            } else {
                $anchor = preg_replace( '/\s+/', '-', preg_replace('/[^a-z\s]/', '', strtolower( $heading[3] ) ) );
                $add_id = true;
            }

            if ( !in_array( $anchor, $anchors ) ) {
                $anchors[] = $anchor;
            } else {
                $orig_anchor = $anchor;
                $i = 2;
                while ( in_array( $anchor, $anchors ) ) {
                    $anchor = $orig_anchor.'-'.$i;
                    $i++;
                }
                $anchors[] = $anchor;
            }

            if ( $add_id ) {
                // This section is where you can mess with the 'to home arrow and remove it entirely if you don't want it
                // It is in the <span><a href="#.... section on the next line. Add a class to the span if you want to style it in your CSS
                $content = substr_replace( $content, '<h'.$lvl.' id="'.$anchor.'"'.$heading[2].'><a href="#'.$anchor.'" class="link-anchor">'.$heading[3].'</a></h'.$lvl.'>', strpos( $content, $heading[0] ), strlen( $heading[0] ) );
            }

            $ret = preg_match( '/title=[\'|"](.*)?[\'|"]/i', stripslashes( $heading[2] ), $title );
            if ( $ret && $title[1] != '' )
                $title = stripslashes( $title[1] );
            else
                $title = $heading[3];
            $title      = trim( strip_tags( $title ) );

            if ($i > 0) {
                if ($prevlvl < $lvl) {
                    $toc .= "\n"."<ol>"."\n";
                } else if ($prevlvl > $lvl) {
                    $toc .= '</li>'."\n";
                    while ($prevlvl > $lvl) {
                        $toc .= "</ol>"."\n".'</li>'."\n";
                        $prevlvl--;
                    }
                } else {
                    $toc .= '</li>'."\n";
                }
            }

            $j = 0;
            $toc .= '<li><a href="#'.$anchor.'" data-scroll>'.$title.'</a>';
            $prevlvl = $lvl;

            $i++;
        }

        unset( $anchors );

        while ( $lvl > $startlvl ) {
            $toc .= "\n</ol>";
            $lvl--;
        }

        $toc .= '</li>'."\n";
        $toc .= '</ol>'."\n";

        return array(
            'toc' => $toc,
            'content' => $content
        );
    }
}
