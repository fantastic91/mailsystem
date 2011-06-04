<?php
/**
 * @file
 * Copy of drupal_html_to_text improvements from issue #299138.
 */

/**
 * Perform format=flowed soft wrapping for mail (RFC 3676).
 *
 * We use delsp=yes wrapping, but only break non-spaced languages when
 * absolutely necessary to avoid compatibility issues.
 *
 * We deliberately use variable_get('mail_line_endings', MAIL_LINE_ENDINGS)
 * rather than "\r\n".
 *
 * @param $text
 *   The plain text to process.
 * @param array $options
 *   (optional) An array containing one or more of the following keys:
 *   - indent: A string to indent the text with. Only '>' characters are
 *     repeated on subsequent wrapped lines. Others are replaced by spaces.
 *   - max: The maximum length at which to wrap each line. Defaults to 80.
 *   - stuff: Whether to space-stuff special lines.  Defaults to TRUE.
 *   - hard: Whether to enforce the maximum line length even if no convenient
 *     space character is available.  Defaults to FALSE.
 *   - pad: A string to use for padding short lines to 'max' characters.  If
 *     more than one character, only the last will be repeated.
 *   - break: The line break sequence to insert.  The default is one of the
 *     following:
 *     - "\r\n": Windows, when $text does not contain a space character.
 *     - "\n": Non-Windows, when $text does not contain a space character.
 *     - " \r\n": On Windows, when $text contains at least one space.
 *     - " \n": Non-Windows, when $text contains at least one space.
 *
 * @see drupal_mail()
 */
function mailsystem_wrap_mail($text, array $options = array()) {
  static $defaults;
  if (!isset($defaults)) {
    $defaults = array(
      'indent' => '',
      'pad' => '',
      'pad_repeat' => '',
      'max' => 80,
      'stuff' => TRUE,
      'hard' => FALSE,
      'eol' => variable_get('mail_line_endings', MAIL_LINE_ENDINGS),
    );
  }
  $options += $defaults;
  if (!isset($options['break'])) {
    // Allow soft-wrap spaces only when $text contains at least one space.
    $options['break'] = (strpos($text, ' ') === FALSE ? '' : ' ') . $defaults['eol'];
  }
  if ($options['pad']) {
    $options['pad_repeat'] = drupal_substr($options['pad'], -1, 1);
  }
  $options['length'] = drupal_strlen($options['indent'] . $options['break']);
  // The 'clean' indent is applied to all lines after the first one.
  $options['clean'] = _mailsystem_html_to_text_clean($options['indent']);
  // Replace line breaks with platform-dependent text.
  $text = preg_replace('/\r?\n/', $defaults['eol'], $text);
  // Wrap lines according to RFC 3676.
  $lines = explode($defaults['eol'], $text);
  array_walk($lines, '_mailsystem_wrap_mail_line', $options);
  // Expand the lines array on newly-inserted line breaks.
  $lines = explode($defaults['eol'], implode($defaults['eol'], $lines));
  // Apply indentation, space-stuffing, and padding.
  array_walk($lines, '_mailsystem_indent_mail_line', $options);
  return implode($defaults['eol'], $lines);
}

/**
 * Transform an HTML string into plain text, preserving the structure of the
 * markup. Useful for preparing the body of a node to be sent by e-mail.
 *
 * The output will be suitable for use as 'format=flowed; delsp=yes' text
 * (RFC 3676) and can be passed directly to drupal_mail() for sending.
 *
 * We deliberately use variable_get('mail_line_endings', MAIL_LINE_ENDINGS)
 * rather than "\r\n".
 *
 * This function provides suitable alternatives for the following tags:
 *
 * <a> <address> <b> <blockquote> <br /> <caption> <cite> <dd> <div> <dl> <dt>
 * <em> <h1> <h2> <h3> <h4> <h5> <h6> <hr /> <i> <li> <ol> <p> <pre> <strong>
 * <table> <tbody> <td> <tfoot> <thead> <tr> <u> <ul>
 *
 * The following tag attributes are supported:
 * - <a href=...>: Hyperlink destination urls.
 * - <li value=...>: Ordered list item numbers.
 * - <ol start=...>: Ordered list start number.
 *
 * @param $string
 *   The string to be transformed.
 * @param $allowed_tags
 *   (optional) If supplied, a list of tags that will be transformed. If
 *   omitted, all supported tags are transformed.
 *
 * @return
 *   The transformed string.
 *
 * @see drupal_mail()
 */
function mailsystem_html_to_text($string, $allowed_tags = NULL) {
  $eol = variable_get('mail_line_endings', MAIL_LINE_ENDINGS);
  // Cache list of supported tags.
  static $supported_tags;
  if (!isset($supported_tags)) {
    $supported_tags = array(
      'a', 'address', 'b', 'blockquote', 'br', 'cite', 'dd', 'div', 'dl',
      'dt', 'em', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'hr', 'i', 'li',
      'ol', 'p', 'pre', 'strong', 'table', 'td', 'tr', 'u', 'ul',
    );
  }

  // Make sure only supported tags are kept.
  $allowed_tags = isset($allowed_tags) ? array_intersect($supported_tags, $allowed_tags) : $supported_tags;

  // Parse $string into a DOM tree.
  $dom = filter_dom_load($string);
  $notes = array();
  $text = _mailsystem_html_to_text($dom->documentElement, $allowed_tags, $notes);
  // Hard-wrap at 1000 characters and space-stuff.
  $text = mailsystem_wrap_mail($text, array('max' => 1000, 'hard' => TRUE));
  // Change non-breaking spaces back to regular spaces, and trim line breaks.
  // chr(160) is the non-breaking space character.
  $text = trim(str_replace(chr(160), ' ', $text), $eol);
  // Add footnotes;
  if ($notes) {
    // Add a blank line before the footnote list.
    $text .= $eol;
    foreach ($notes as $url => $note) {
      $text .= $eol . '[' . $note . '] ' . $url;
    }
  }
  return $text;
}

/**
 * Helper function for drupal_html_to_text().
 *
 * Recursively converts $node to text, wrapping and indenting as necessary.
 *
 * @param $node
 *   The source DOMNode.
 * @param $allowed_tags
 *   A list of tags that will be transformed.
 * @param array &$notes
 *   A writeable array of footnote reference numbers, keyed by their
 *   respective hyperlink destination urls.
 * @param $line_length
 *   The maximum length of a line, for wrapping.  Defaults to 80 characters.
 * @param array $parents
 *   The list of ancestor tags, from nearest to most distant.  Defaults to an
 *   empty array().
 * @param $count
 *   The number to use for the next list item within an ordered list.  Defaults
 *   to 1.
 */
function _mailsystem_html_to_text(DOMNode $node, array $allowed_tags, array &$notes, $line_length = 80, array $parents = array(), &$count = NULL) {
  if (!isset($count)) {
    $count = 1;
  }
  $eol = variable_get('mail_line_endings', MAIL_LINE_ENDINGS);
  if ($node->nodeType === XML_TEXT_NODE) {
    // For text nodes, we just copy the text content.
    $text = $node->textContent;
    if (in_array('pre', $parents)) {
      // Within <pre> tags, all spaces become non-breaking.
      // chr(160) is the non-breaking space character.
      $text = str_replace(' ', chr(160), $text);
    }
    else {
      // Outside <pre> tags, collapse whitespace.
      $text = preg_replace('/[[:space:]]+/', ' ', $text);
    }
    return $text;
  }
  // Non-text node.
  $tag = '';
  $text = '';
  $child_text = '';
  $child_count = 1;
  $indent = '';
  $prefix = '';
  $suffix = '';
  $pad = '';
  if (isset($node->tagName) && in_array($node->tagName, $allowed_tags)) {
    $tag = $node->tagName;
    switch ($tag) {
      // Turn links with valid hrefs into footnotes.
      case 'a':
        $test = !empty($node->attributes);
        $test = $test && ($href = $node->attributes->getNamedItem('href'));
        $test = $test && ($url = url(ltrim($href->nodeValue, '/'), array('absolute' => TRUE)));
        $test = $test && valid_url($url);
        if ($test) {
          // Only add links that have not already been added.
          if (isset($notes[$url])) {
            $note = $notes[$url];
          }
          else {
            $note = count($notes) + 1;
            $notes[$url] = $note;
          }
          $suffix = ' [' . $note . ']';
        }
        break;

      // Generic block-level tags.
      case 'address':
      case 'caption':
      case 'div':
      case 'p':
      case 'pre':
        $text = $eol;
        $suffix = $eol;
        break;

      // Forced line break.
      case 'br':
        $text = $eol;
        break;

      // Boldface by wrapping with "*" characters.
      case 'b':
      case 'strong':
        $prefix = '*';
        $suffix = '*';
        break;

      // Italicize by wrapping with "/" characters.
      case 'cite':
      case 'em':
      case 'i':
        $prefix = '/';
        $suffix = '/';
        break;

      // Underline by wrapping with "_" characters.
      case 'u':
        $prefix = '_';
        $suffix = '_';
        break;

      // Blockquotes are indented by "> " at each level.
      case 'blockquote':
        $text = $eol;
        // chr(160) is the non-breaking space character.
        $indent = '>' . chr(160);
        $suffix = $eol;
        break;

      // Dictionary definitions are indented by four spaces.
      case 'dd':
        // chr(160) is the non-breaking space character.
        $indent = chr(160) . chr(160) . chr(160) . chr(160);
        $suffix = $eol;
        break;

      // Dictionary list.
      case 'dl':
        // Start on a newline except inside other lists.
        if (!in_array('li', $parents)) {
          $text = $eol;
        }
        $suffix = $eol;
        break;

      // Dictionary term.
      case 'dt':
        $suffix = $eol;
        break;

      // Header level 1 is prefixed by eight "=" characters.
      case 'h1':
        $text = "$eol$eol";
        // chr(160) is the non-breaking space character.
        $indent = '========' . chr(160);
        $pad = chr(160) . '=';
        $suffix = $eol;
        break;

      // Header level 2 is prefixed by six "-" characters.
      case 'h2':
        $text = "$eol$eol";
        // chr(160) is the non-breaking space character.
        $indent = '------' . chr(160);
        $pad = chr(160) . '-';
        $suffix = $eol;
        break;

      // Header level 3 is prefixed by four "." characters and a space.
      case 'h3':
        $text = "$eol$eol";
        // chr(160) is the non-breaking space character.
        $indent = '....' . chr(160);
        $suffix = $eol;
        break;

      // Header level 4 is prefixed by three "." characters and a space.
      case 'h4':
        $text = "$eol$eol";
        // chr(160) is the non-breaking space character.
        $indent = '...' . chr(160);
        $suffix = $eol;
        break;

      // Header level 5 is prefixed by two "." character and a space.
      case 'h5':
        $text = "$eol$eol";
        // chr(160) is the non-breaking space character.
        $indent = '..' . chr(160);
        $suffix = $eol;
        break;

      // Header level 6 is prefixed by one "." character and a space.
      case 'h6':
        $text = "$eol$eol";
        // chr(160) is the non-breaking space character.
        $indent = '.' . chr(160);
        $suffix = $eol;
        break;

      // Horizontal rulers become a line of "-" characters.
      case 'hr':
        $text = $eol;
        $child_text = '-';
        $pad = '-';
        $suffix = $eol;
        break;

      // List items are treated differently depending on the parent tag.
      case 'li':
        // Ordered list item.
        if (reset($parents) === 'ol') {
          // Check the value attribute.
          $test = !empty($node->attributes);
          $test = $test && ($value = $node->attributes->getNamedItem('value'));
          if ($test) {
            $count = $value->nodeValue;
          }
          // chr(160) is the non-breaking space character.
          $indent = ($count < 10 ? chr(160) : '') . chr(160) . "$count)" . chr(160);
          $count++;
        }
        // Unordered list item.
        else {
          // chr(160) is the non-breaking space character.
          $indent = chr(160) . '*' . chr(160);
        }
        $suffix = $eol;
        break;

      // Ordered lists.
      case 'ol':
        // Start on a newline except inside other lists.
        if (!in_array('li', $parents)) {
          $text = $eol;
        }
        // Check the start attribute.
        $test = !empty($node->attributes);
        $test = $test && ($value = $node->attributes->getNamedItem('start'));
        if ($test) {
          $child_count = $value->nodeValue;
        }
        break;

      // Tables require special handling.
      case 'table':
        return _mailsystem_html_to_text_table($node, $allowed_tags, $notes, $line_length);

      // Separate adjacent table cells by two non-breaking spaces.
      case 'td':
        if (!empty($node->nextSibling)) {
          // chr(160) is the non-breaking space character.
          $suffix = chr(160) . chr(160);
        }
        break;

      // End each table row with a newline.
      case 'tr':
        $suffix = $eol;
        break;

      // Unordered lists.
      case 'ul':
        // Start on a newline except inside other lists.
        if (!in_array('li', $parents)) {
          $text = $eol;
        }
        break;

      default:
        // Coder review complains if there is no default case.
        break;
    }
    // Only add allowed tags to the $parents array.
    array_unshift($parents, $tag);
  }
  // Copy each child node to output.
  if ($node->hasChildNodes()) {
    foreach ($node->childNodes as $child) {
      $child_text .= _mailsystem_html_to_text($child, $allowed_tags, $notes, $line_length - drupal_strlen($indent), $parents, $child_count);    }
  }
  // We only add prefix and suffix if the child nodes were non-empty.
  if ($child_text > '') {
    // We capitalize the contents of h1 and h2 tags.
    if ($tag === 'h1' || $tag === 'h2') {
      $child_text = drupal_strtoupper($child_text);
    }
    // Don't add a newline to an existing newline.
    if ($suffix === $eol && drupal_substr($child_text, - drupal_strlen($eol)) === $eol) {
      $suffix = '';
    }
    // Trim spaces around newlines except with <pre> or inline tags.
    if (!in_array($tag, array('a', 'b', 'cite', 'em', 'i', 'pre', 'strong', 'u'))) {
      $child_text = preg_replace('/ *\n */', "\n", $child_text);
    }
    // Soft-wrap at effective line length, but don't space-stuff.
    $child_text = mailsystem_wrap_mail(
      $prefix . $child_text,
      array(
        'break' => chr(160) . $eol,
        'indent' => $indent,
        'max' => $line_length,
        'pad' => $pad,
        'stuff' => FALSE,
      )
    ) . $suffix;
    if ($tag === 'pre') {
      // Perform RFC-3676 soft-wrapping.
      $child_text = str_replace(chr(160), ' ', $child_text);
      $child_text = mailsystem_wrap_mail(
        $child_text,
        array('max' => $line_length, 'stuff' => FALSE)
      );
      $child_text = str_replace(' ', chr(160), $child_text);
    }
    $text .= $child_text;
  }
  return $text;
}

/**
 * Helper function for _mailsystem_html_to_text().
 *
 * Renders a <table> DOM Node into plain text.  Attributes such as rowspan,
 * colspan, padding, border, etc. are ignored.
 *
 * @param DOMNode $node
 *   The DOMNode corresponding to the <table> tag and its contents.
 * @param $allowed_tags
 *   The list of allowed tags passed to _mailsystem_html_to_text().
 * @param array &$notes
 *   A writeable array of footnote reference numbers, keyed by their
 *   respective hyperlink destination urls.
 * @param $line_length
 *   The desired maximum line length, after word-wrapping each table cell.
 *
 * @return
 *   A plain text representation of the table.
 *
 * @see _mailsystem_html_to_text()
 */
function _mailsystem_html_to_text_table(DOMNode $node, $allowed_tags = NULL, array &$notes = array(), $line_length = 80) {
  $eol = variable_get('mail_line_endings', MAIL_LINE_ENDINGS);
  $header = array();
  $footer = array();
  $body = array();
  $text = '';
  $text = '';
  $current = $node;
  while (TRUE) {
    if (isset($current->tagName)) {
      switch ($current->tagName) {
        case 'caption':  // The table caption is added first.
          $text = _mailsystem_html_to_text($caption, $allowed_tags, $notes, $line_length);
          break;

        case 'tr':
          switch ($current->parentNode->tagName) {
            case 'thead':
              $header[] = $current;
              break;

            case 'tfoot':
              $footer[] = $current;
              break;

            default: // Either 'tbody' or 'table'
              $body[] = $current;
              break;
          }
          break;

        default:
          if ($current->hasChildNodes()) {
            $current = $current->firstChild;
            continue 2;
          }
      }
    }
    do {
      if ($current->nextSibling) {
        $current = $current->nextSibling;
        continue 2;
      }
      $current = $current->parentNode;
    } while (!$current->isSameNode($node));
    break;
  }
  // Merge the thead, tbody, and tfoot sections together.
  if ($rows = array_merge($header, $body, $footer)) {
    // First just count the number of columns.
    $columns = 0;
    foreach ($rows as $row) {
      if ($row->childNodes) {
        $columns = max($columns, $row->childNodes->length);
      }
    }
    if ($columns) {
      // If any columns were found, calculate each column height and width.
      $width = $line_length - $columns + 1;
      $table_width = 1000;
      $table = array();
      // Find an appropriate width to wrap each cell.
      do {
        // Start with zero for each width and height, and and empty cell array.
        $widths = array_fill(0, $columns, 0);
        $heights = array_fill(0, count($rows), 0);
        $cells = array();
        $breaks = array();
        foreach ($rows as $i => $row) {
          foreach ($row->childNodes as $j => $cell) {
            $z = 0;
            // Render the cell contents.
            $cells[$i][$j] = _mailsystem_html_to_text($cell, $allowed_tags, $notes, $width);
            // Split the cell into lines.
            $lines = explode($eol, $cells[$i][$j]);
            // The row height is the maximum number of lines among all the
            // cells in that row.
            $heights[$i] = max($heights[$i], count($lines));
            foreach ($lines as $line) {
              $this_width = drupal_strlen($line);
              // The column width is the maximum line width among all the
              // lines in that column.
              if ($this_width > $widths[$j]) {
                $widths[$j] = $this_width;
                // If the longest line in a column contains at least one
                // space character, then the table can be made narrower.
                $breaks[$j] = strpos(' ', $line) !== FALSE;
              }
            }
          }
        }
        // Calculate the total table width;
        $this_width = array_sum($widths) + $columns + 1;
        if ($this_width < $table_width) {
          $table_width = $this_width;
          // Save this configuration in case it turns out to be the best one.
          $table = array($widths, $heights, $cells);
          if ($table_width <= $line_length) {
            // Table width is within limits; we're done.
            break;
          }
        }
        elseif (!in_array(TRUE, $breaks)) {
          // If there are no more break points, then the table is already as
          // narrow as it can get, so we're done.
          break;
        }
        // Try wrapping each column at a smaller width.
        $width--;
      } while ($width > 1);
      // Retrieve the best configuration that was found.
      list ($widths, $heights, $cells) = $table;
      // Pad each cell to column width and line height.
      foreach ($cells as $i => $row) {
        foreach ($row as $j => $cell) {
          // Pad each cell to the maximum number of lines in that row.
          $lines = array_pad(explode($eol, $cell), $heights[$i], '');
          foreach ($lines as $k => $line) {
            // Pad each line to the maximum width in that column.
            $count = $widths[$j] - drupal_strlen($line);
            if ($count > 0) {
              $lines[$k] .= str_repeat(' ', $count);
            }
          }
          $cells[$i][$j] = $lines;
        }
      }
      // Generate the row separator line.
      $row_line = '+';
      foreach ($widths as $width) {
        $row_line .= str_repeat('-', $width) . '+';
      }
      $row_line .= $eol;
      // Final output starts with a row separator.
      $text .= $row_line;
      foreach ($cells as $i => $row) {
        // For each row, iterate first by lines within the row.
        for ($k = 0; $k < $heights[$i]; $k++) {
          // Add a vertical-bar at the beginning of each row line.
          $text .= '|';
          // Within each row line, iterate by cells within that line.
          foreach ($row as $j => $lines) {
            // Add a vertical bar at the end of each cell line.
            $text .= $lines[$k] . '|';
          }
          // Add a line break at the end of each row line.
          $text .= $eol;
        }
        // Add a row separator at the end of each row.
        $text .= $row_line;
      }
    }
  }
  return $text;
}

/**
 * Helper function for array_walk in drupal_wrap_mail().
 *
 * Inserts $values['break'] sequences to break up $line into parts of no more
 * than $values['max'] - $values['length'] characters.  Only breaks at space
 * characters, unless $values['hard'] is TRUE.
 */
function _mailsystem_wrap_mail_line(&$line, $key, $values) {
  $line = wordwrap(rtrim($line), $values['max'] - $values['length'], $values['break'], $values['hard']);
}

/**
 * Helper function for array_walk in drupal_wrap_mail().
 *
 * If $values['pad'] is non-empty, $values['indent'] will be added at the start
 * of each line, and $values['pad'] at the end, repeating the last character of
 * $values['pad'] until the line length equals $values['max'].
 *
 * If $values['pad'] is empty, $values['indent'] will be added at the start of
 * the first line, and $values['clean'] at the start of subsequent lines.
 *
 * If $values['stuff'] is true, then an extra space character will be added at
 * the start of any line beginning with a space, a '>', or the word 'From'.
 *
 * @see http://www.ietf.org/rfc/rfc3676.txt
 */
function _mailsystem_indent_mail_line(&$line, $key, $values) {
  if ($line == '') {
    return;
  }
  if ($values['pad']) {
    $line = $values['indent'] . $line;
    $count = $values['max'] - drupal_strlen($line) - drupal_strlen($values['pad']);
    if ($count >= 0) {
      $line .= $values['pad'] . str_repeat($values['pad_repeat'], $count);
    }
  }
  else {
    $line = $values[$key === 0 ? 'indent' : 'clean'] . $line;
  }
  if ($values['stuff']) {
    // chr(160) is the non-breaking space character.
    $line = preg_replace('/^(' . chr(160) . '| |>|From)/', ' $1', $line);
  }
}

/**
 * Helper function for drupal_wrap_mail() and drupal_html_to_text().
 *
 * Replace all non-quotation markers from a given piece of indentation with
 * non-breaking space characters.
 */
function _mailsystem_html_to_text_clean($indent) {
  // chr(160) is the non-breaking space character.
  return preg_replace('/[^>]/', chr(160), $indent);
}
