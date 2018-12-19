<?php
/**
 * User: Paul Coudeville <paul@metabolism.fr>
 */

namespace Rocket\Composer\Command;


use Composer\Command\BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CleanupCommand extends BaseCommand
{

    /**
     * Command declaration
     */
    protected function configure()
    {
        $this->setName( 'cleanup' );
        $this->setDescription("Remove temporary files from Rocket Framework");
        $this->setHelp( <<<EOT
        
<comment>----------------- COMPOSER CLEANUP -----------------</comment> 
        
This commands will remove several folders from the project : 
 - doc/
 - var/*
 - web/public/media/tmp

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

        $cleaned_dirs = [
            'doc/',
            'var/*',
            'web/static/media/tmp'
        ];

        $msg = "Following files will be deleted :\n";

        foreach ($cleaned_dirs as $file)
            $msg .= " - $file\n";

        $this->getIO()->write("<comment>$msg</comment>");
        $confirm = $this->getIO()->askConfirmation("Are you sure to clean directories ? [yes/no] ", false);

        if ($confirm)
        {
            foreach ($cleaned_dirs as $file)
                passthru('rm -r '.$file);

            $this->getIO()->write("<info>Successfully removed</info>");
        }

        $this->getIO()->write("<info>Aborded</info>");
    }
}
