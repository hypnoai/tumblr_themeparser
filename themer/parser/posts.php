<?php
/**
 * Themer
 *
 * A Tumblr theme parser for local development.
 *
 * @package   Themer
 * @author    Braden Schaeffer 
 * @version   beta
 * @link      http://github.com/bschaeffer/themer
 *
 * @copyright Copyright (c) 2011
 * @license   http://www.opensource.org/licenses/mit-license.html MIT
 *
 * @filesource
 */
namespace Themer\Parser;

use Themer\Data;
use Themer\Parser\Block;
use Themer\Parser\Variable;

/**
 * Themer Posts Class
 * 
 * Renders Tumblr's {block:Posts} block along with all relevant
 * information
 * 
 * @package     Themer
 * @subpackage  Parser
 * @author      Braden Schaeffer
 */
class Posts {
  
  /**
   * Renders {block:Posts} blocks in the given theme
   *
   * @static
   * @access  public
   * @param   string  the theme contents to render
   * @param   array   the post data to use
   * @return  string  the parsed theme contents
   */
  public static function render($theme, $post_data)
  {
    $posts = Block::find($theme, 'Posts');
    ##exit(" Got this far in rendering posts!.");
    
    foreach($posts as $post)
    { 
      print("rendering a post!");
      $tmp = Block::render($post, 'Posts');
      $tmp = self::_render_posts($tmp, $post_data);
      $theme = str_replace($post, $tmp, $theme);
    }
    
    return $theme;
  }
  
  /**
   * Renders post data for a single {block:Posts} block
   *
   * @static
   * @access  private
   * @param   string  the post block to parse
   * @param   array   the post data to use
   * @return  string  the parsed post block
   */
  public static function _render_posts($block, $post_data)
  { 
    if(empty($post_data))
    {
      return '';
    }
    
    $rendered = '';
  
    foreach($post_data as $index => $post)
    {
      $index = $index + 1;
      $offset = (($index) % 2) ? 'Odd' : 'Even';
      
      print('render posts');
      $tmp = Block::render($block, 'Post'.$index);
      $tmp = Block::render($tmp, $offset);
      $tmp = Block::render($tmp, $post['PostType']);

      if(isset($post['Tags']))
      {
        $tmp = self::_render_tags($tmp, $post['Tags']);
        unset($post['Tags']);
      }
      
      foreach($post as $k => $v)
      {
        print("Printing... a post..");
        // Is it a private variable? If so, skip it.
        if(strpos($k, '_') === 0) break;
        
        if( ! is_array($v))
        {
          $tmp = Variable::render($tmp, $k, $v);
        }
        else
        {
          $tmp = self::_render_array($tmp, $k, $v);
        }
        
        // Render stuff like {block:Title} for text posts or
        // {block:Caption} for photos, etc..
        
        if(Block::find($tmp, $k) && ! empty($v))
        {
          $tmp = Block::render($tmp, $k);
        }
      }
      
      $rendered .= Block::cleanup($tmp);
    }
    
    return $rendered;
  }
  
  /**
   * Renders {block:Tags} blocks found within a given post block
   *
   * @static
   * @access  private
   * @param   string  the post block to use
   * @param   array   the tag data
   * @return  string  the parsed post block
   */
  private static function _render_tags($block, $tags)
  {
    if(empty($tags))
    {
      $block = Block::remove($block, 'HasTags');
      $block = Block::remove($block, 'Tags');
      return $block;
    }
    
    $rendered = '';
    $block = Block::render($block, 'HasTags');
    
    foreach(Block::find($block, 'Tags') as $tb)
    {
      $rendered = '';
      $cache = Block::render($tb, 'Tags');
      
      foreach($tags as $tag)
      {
        $safe = urlencode($tag);
        $url = '/tagged/'.str_replace(array(' ', '%20'), '+', $safe);
        $chrono = $url.'/chrono';
        
        $tmp = Variable::render($cache, 'Tag',          $tag, FALSE);
        $tmp = Variable::render($tmp,   'URLSafeTag',   $safe, FALSE);
        $tmp = Variable::render($tmp,   'TagURL',       $url, FALSE);
        $tmp = Variable::render($tmp,   'TagURLChrono', $chrono, FALSE);
        
        $rendered .= $tmp;
      }

      $block = str_replace($tb, $rendered, $block);
    }
    
    return $block;
  }
  
  /**
   * Renders post type blocks like {block:Lines} for chat posts, etc.
   * 
   * @static
   * @access  private
   * @param   string  the block to use
   * @param   string  the block Template tag
   * @param   array   the block data
   * @return  string  the parsed block
   */
  private static function _render_array($block, $tag, $data)
  {
    foreach(Block::find($block, $tag) as $b)
    {
      $rendered = '';
      $tmp = Block::render($b, $tag);
      
      foreach($data as $k => $v)
      {
        // We have to cache the template here so we can reuse
        // it inside the loop
    
        $cache = $tmp;
        
        if(is_array($v))
        {
          foreach($v as $kk => $vv)
          {
            $cache = Variable::render($cache, $kk, $vv);
          }
          
          $rendered .= $cache;
        }
        else
        {
          $rendered .= Variable::render($cache, $k, $v);
        }
      }
      
      $block = str_replace($b, $rendered, $block);
    }
    
    return $block;
  }
}

/* End of file posts.php */
/* Location: ./themer/parser/posts.php */