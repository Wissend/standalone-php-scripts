<?php
/**
 * Output entities in columns
 *
 * Usage:
 *     $instance = new ColumnizeEntities();
 *     $params = array();
 *     echo $instance($params);
 *
 * @author  Zion Ng <zion@intzone.com>
 * @link    [Source] https://github.com/zionsg/standalone-php-scripts/tree/master/ColumnizeEntities
 * @since   2012-11-06T23:30+08:00
 */
class ColumnizeEntities
{
    /**
     * Method invoked when script calls instance as a function
     *
     * Output entities in columns
     *
     * For each entity, its thumbnail image and name are shown and both are
     * hyperlinked to a specified url. If another format is desired,
     * $entityCallback can be used
     *
     * In the scenario where the no. of entities is not divisible by the no.
     * of columns, the remaining entities are centered in the last row
     *
     * @param array $params Key-value pairs. All paths should NOT have trailing slashes
     *                      @key int      $cols         DEFAULT=1. No. of columns to split entities in
     *                      @key object[] $entities     Array of entity objects
     *                      @key string   $entityCallback Callback function that takes in entity and returns formatted
     *                                                    HTML for entity. If this is not defined, the default format
     *                                                    of url, thumbnail and name is used
     *                      @key string   $nameClass    CSS class for entity name
     *                      @key string   $nameCallback Callback function that takes in entity and returns name
     *                      @key boolean  $leftToRight  DEFAULT=true. Whether to list entities from left to right
     *                                                  or top to down.
     *                                                  Eg: Left to right
     *                                                      1   2   3
     *                                                      4   5   6
     *                                                        7   8
     *
     *                                                      Top to down
     *                                                      1   3   5
     *                                                      2   4   6
     *                                                        7   8
     *                      @key string   $tableClass   CSS class for entire table
     *                      @key string   $tableId      'id' attribute for entire table, to facilitate DOM reference
     *                      @key string   $tdClass      CSS class for <td> enclosing entity
     *                      @key string   $trClass      CSS class for <tr> enclosing entity <td>
     *                      @key string   $urlCallback  Callback function that takes in entity and returns entity url
     *                      @key string   $urlClass     CSS class for entity url
     *                      @key string   $urlTarget    Target for entity url. <a target="<$urlTarget>"...
     *
     *                      Keys for drawing thumbnail images:
     *                      @key boolean $drawThumbnailBox   DEFAULT=true. Whether to enclose thumbnail <img> in <td>.
     *                                                       If true, box will be drawn even if there's no thumbnail
     *                      @key string  $thumbnailBoxClass  CSS class for <td> box enclosing thumbnail image
     *                      @key string  $thumbnailClass     CSS class for thumbnail image
     *                      @key string  $thumbnailCallback  Callback function that takes in entity and returns
     *                                                       thumbnail filename
     *                      @key string  $thumbnailPath      Folder path relative to web root where thumbnail is stored
     *                      @key int     $maxThumbnailHeight Maximum height constraint for thumbnail image
     *                                                       If set to 0, "height" attribute will be skipped in output
     *                      @key int     $maxThumbnailWidth  Maximum width constraint for thumbnail image
     *                                                       If set to 0, "width" attribute will be skipped in output
     *                      @key string  $webRoot            Absolute path for web root. Used for retrieving thumbnail
     * @return string
     */
    public function __invoke(array $params)
    {
        // Make sure all keys are set before extracting to prevent notices
        $params = array_merge(
            array(
                'cols' => 1,
                'entities' => array(),
                'entityCallback' => null,
                'nameClass' => '',
                'nameCallback' => null,
                'leftToRight' => true,
                'tableClass' => '',
                'tableId' => '',
                'tdClass' => '',
                'trClass' => '',
                'urlCallback' => null,
                'urlClass' => '',
                'urlTarget' => '',
                // keys for drawing thumbnails
                'drawThumbnailBox' => true,
                'thumbnailBoxClass' => '',
                'thumbnailCallback' => null,
                'thumbnailClass' => '',
                'thumbnailPath' => '',
                'maxThumbnailHeight' => 0,
                'maxThumbnailWidth' => 0,
                'webRoot' => '',
            ),
            $params
        );
        extract($params);

        $entityCount = count($entities);
        if ($entityCount == 0) return '';

        $cols = min($cols, $entityCount); // no point displaying 2 items in 3 cols
        $initialRows = (int) floor($entityCount / $cols);
        $tdWidth = 100 / $cols;
        $entitiesProcessed = 0;

        $output = "<table id=\"{$tableId}\" class=\"{$tableClass}\" width=\"100%\">" . PHP_EOL;
        for ($row = 0; $row < $initialRows; $row++) {
            $output .= "<tr class=\"{$trClass}\">" . PHP_EOL;
            for ($col = 0; $col < $cols; $col++) {
                $output .= "<td class=\"{$tdClass}\" width=\"{$tdWidth}%\">" . PHP_EOL;

                // Get entity, depending on listing order (left-right or top-down)
                if ($leftToRight) {
                    $index = ($row * $cols) + $col;
                } else {
                    $index = ($col * $initialRows) + $row;
                }
                if ($index >= $entityCount) {
                    continue;
                }
                $entity = $entities[$index];

                // Get entity output
                $entityOutput = '';
                if ($entityCallback) {
                    $entityOutput = $entityCallback($entity) . PHP_EOL;
                } else {
                    // Get entity url
                    $url = null;
                    if ($urlCallback) {
                        $url = $urlCallback($entity);
                    }

                    // Get entity name
                    $name = null;
                    if ($nameCallback) {
                        $name = $nameCallback($entity);
                    }

                    // Get entity thumbnail
                    $thumbnail = null;
                    if ($thumbnailCallback) {
                        $thumbnail = $thumbnailCallback($entity);
                    }

                    // Draw thumbnail
                    $thumbnailOutput = '';
                    if ($thumbnail !== null) {
                        $imagePath = $webRoot . $thumbnailPath . '/' . $thumbnail;
                        if (!file_exists($imagePath)) {
                            $thumbnailOutput .= PHP_EOL;
                        } else {
                            list($width, $height, $type, $attr) = getimagesize($imagePath);

                            if ($maxThumbnailWidth != 0 && $width > $maxThumbnailWidth) {
                                $height = ($height / $width) * $maxThumbnailWidth;
                                $width  = $maxThumbnailWidth;
                            }

                            if ($maxThumbnailHeight != 0 && $height > $maxThumbnailHeight) {
                                $width  = ($width / $height) * $maxThumbnailHeight;
                                $height = $maxThumbnailHeight;
                            }

                            $thumbnailOutput = sprintf(
                                '<img %s src="%s" %s %s />' . PHP_EOL,
                                ($thumbnailClass ? "class=\"{$thumbnailClass}\"" : ''),
                                $thumbnailPath . '/' . $thumbnail,
                                ($maxThumbnailWidth == 0 ? '' : "width=\"{$width}\""),
                                ($maxThumbnailHeight == 0 ? '' : "height=\"{$height}\"")
                            );
                        } // end if thumbnail file exists

                        if ($drawThumbnailBox) {
                            $thumbnailOutput = sprintf(
                                '<table align="center" cellspacing="0" cellpadding="0">' . PHP_EOL
                                . '<tr><td %s %s %s align="center" valign="middle">' . PHP_EOL
                                . '%s'
                                . '</td></tr>' . PHP_EOL
                                . '</table>' . PHP_EOL,
                                ($thumbnailBoxClass ? "class=\"{$thumbnailBoxClass}\"" : ''),
                                ($maxThumbnailWidth == 0 ? '' : "width=\"{$maxThumbnailWidth}\""),
                                ($maxThumbnailHeight == 0 ? '' : "height=\"{$maxThumbnailHeight}\""),
                                $thumbnailOutput
                            );
                        }
                    } // end draw thumbnail

                    // Output entity
                    if ($url !== null) {
                        $entityOutput .= "<a class=\"{$urlClass}\" target=\"{$urlTarget}\" href=\"{$url}\">" . PHP_EOL;
                    }
                    $output .= $thumbnailOutput;
                    if ($name !== null) {
                        $entityOutput .= "<div class=\"{$nameClass}\">{$name}</div>" . PHP_EOL;
                    }
                    if ($url !== null) {
                        $entityOutput .= '</a>' . PHP_EOL;
                    }
                } // end entity output

                $output .= $entityOutput . '</td>' . PHP_EOL;
                $entitiesProcessed++;
            } // end for cols
            $output .= '</tr>' . PHP_EOL;
        } // end for rows
        $output .= '</table>' . PHP_EOL;

        // Call function again to output remaining entities
        $remainderCount = $entityCount % $cols;
        if ($remainderCount == 0) {
            return $output;
        } else {
            $remainderEntities = array();
            for ($i = $entitiesProcessed; $i < $entityCount; $i++) {
                $remainderEntities[] = $entities[$i];
            }
            $params['cols'] = $remainderCount;
            $params['entities'] = $remainderEntities;
            return $output . $this->__invoke($params);
        }

    } // end function __invoke

} // end class