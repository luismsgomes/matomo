<?php
/**
 * Piwik - Open source web analytics
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 * @category Piwik
 * @package Piwik
 */
namespace Piwik;

/**
 * This class contains logic to make Themes work beautifully.
 *
 * @package Piwik
 */
class Theme
{
    /** @var string  */
    private $themeName;

    /** @var \Piwik\Plugin  */
    private $theme;

    public function __construct()
    {
        $this->theme = \Piwik\Plugin\Manager::getInstance()->getThemeEnabled();
        $this->themeName = $this->theme->getPluginName();
    }

    public function getStylesheet()
    {
        if ($this->themeName == \Piwik\Plugin\Manager::DEFAULT_THEME) {
            return false;
        }

        $info = $this->theme->getInformation();
        if (!isset($info['stylesheet'])) {
            return false;
        }
        $themeStylesheet = 'plugins/' . $this->theme->getPluginName() . '/' . $info['stylesheet'];
        return $themeStylesheet;
    }

    public function rewriteAssetsPathToTheme($output)
    {
        if ($this->themeName == \Piwik\Plugin\Manager::DEFAULT_THEME) {
            return $output;
        }

        $pattern = array(
            // Rewriting scripts includes to overrides
            '~<script type=[\'"]text/javascript[\'"] (src)=[\'"]([^\'"]+)[\'"]>~',
            '~<script (src)=[\'"]([^\'"]+)[\'"] type=[\'"]text/javascript[\'"]>~',
            '~<link (rel)=[\'"]stylesheet[\'"] type=[\'"]text/css[\'"] href=[\'"]([^\'"]+)[\'"] ?/?>~',

            // Images as well
            '~(src|href)=[\'"]([^\'"]+)[\'"]~',

            // rewrite images in CSS files, i.e. url(plugins/Morpheus/overrides/themes/default/images/help.png);
            '~(url\()[\'"]([^\)]?[themes|plugins]+[^\)]+[.jpg|png|gif|svg]?)[\'"][\)]~',

            // rewrites images in JS files
            '~(=)[\s]?[\'"]([^\'"]+[.jpg|.png|.gif|svg]?)[\'"]~',
        );
        return preg_replace_callback($pattern, array($this,'rewriteAssetPathIfOverridesFound'), $output);
    }

    private function rewriteAssetPathIfOverridesFound($src)
    {
        $source = $src[0];
        $pathAsset = $src[2];

        // Basic health check, we dont replace if not starting with plugins/
        if( strpos($pathAsset, 'plugins') !== 0) {
            return $source;
        }

        // or if it's already rewritten
        if(strpos($pathAsset, $this->themeName) !== false) {
            return $source;
        }

        $defaultThemePath = "plugins/" . \Piwik\Plugin\Manager::DEFAULT_THEME;
        $newThemePath = "plugins/" . $this->themeName;
        $overridingAsset = str_replace($defaultThemePath, $newThemePath, $pathAsset);

        if(file_exists($overridingAsset)) {
            return str_replace($pathAsset, $overridingAsset, $source);
        }
        return $source;
    }

}