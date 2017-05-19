<?php
/**
 * User: Paul Coudeville <paul@metabolism.fr>
 */

namespace Rocket\Composer\Command;


use Composer\Command\BaseCommand;
use Rocket\Helper\Parser;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class StyleguideCommand
 *
 * @package Customer\Composer\Plugin\Command
 */
class StyleguideCommand extends BaseCommand
{

    /**
     * Command declaration
     */
    protected function configure()
    {
        $this->setName( 'styleguide' );
        $this->setDescription("Generate a styleguide according to Sass informations and builder configuration file.");
        $this->setHelp( <<<EOT
        
        
<comment>----------------- COMPOSER STYLEGUIDE ----------------- </comment>

Generate a styleguide according to Sass informations and builder configuration file. \n
You can access the generated page by accessing with your favorite browser to /_styleguide/ URI.
The generated style will be automatically place on your web root.

EOT
        );
    }


    /**
     * Command function
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $parser = new Parser($this->getIO());
        $parser->run();
    }
}
