<?php




namespace App\Commands;

use App\Entities\Section;
use App\Lib\Annotations\ORM\References;
use App\Lib\Commands\AbstractCommand;
use App\Lib\Annotations\AnnotationReader;


class TestCommand extends AbstractCommand {
    
    public function execute(): void
    {
        $classAnnotations = AnnotationReader::extractFromClass(Section::class);

        var_dump($classAnnotations->propertiesHaveAnnotation(References::class));
    }

    public function undo(): void
    {
    }

    public function redo(): void
    {
    }
    
}

?>
