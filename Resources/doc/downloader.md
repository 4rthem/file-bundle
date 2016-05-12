
## File downloader

### Usage

Downloader service returns a Arthem uploaded file (`FileInterface`) in order to map it with your model.

```php
use Arthem\Bundle\FileBundle\Model\FileInterface;

$fileDownloader = $container->get('arthem_fileupload.file_downloader');
/** @var FileInterface $file */
$file = $fileDownloader->download('http://customwebsite.com/image.gif');

$myEntity->setFile($file);
$em->persist($myEntity);
$em->flush();
```

[Return to index](index.md)
