<?php
/**
 * This helper defines the content mime types for a /mail/send API call
 *
 * PHP Version - 5.6, 7.0, 7.1, 7.2
 *
 * @package   vendor\SendGrid
 * @author    Elmer Thomas <dx@sendgrid.com>
 * @copyright 2018 SendGrid
 * @license   https://opensource.org/licenses/MIT The MIT License
 * @version   GIT: <git_id>
 * @link      http://packagist.org/packages/sendgrid/sendgrid
 */

namespace vendor\SendGrid;

/**
 * This class is used to define the content mime types for the /mail/send API call
 *
 * @package vendor\SendGrid
 */
abstract class MimeType
{
    const HTML = "text/html";
    const TEXT = "text/plain";
}
