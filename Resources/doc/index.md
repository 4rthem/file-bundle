### Configuration

Update your routing:

```yaml
# app/routing.yml
arthem_file_upload:
    resource: "@ArthemFileUploadBundle/Resources/config/routing.yml"
    prefix: /
```

Add the required assetic on your layout:

```django
{% block javascripts_bottom %}
	...
	{% javascripts
	'@ArthemBaseBundle/Resources/js/arthem.form.js'
	...
	'@ArthemFileUploadBundle/Resources/js/jquery-file-upload/jquery.ui.widget.js'
	'@ArthemFileUploadBundle/Resources/js/jquery-file-upload/jquery.iframe-transport.js'
	'@ArthemFileUploadBundle/Resources/js/jquery-file-upload/jquery.fileupload.js'
	'@ArthemFileUploadBundle/Resources/js/jquery-file-upload/load-image.all.min.js'
	'@ArthemFileUploadBundle/Resources/js/jquery-file-upload/jquery.fileupload-process.js'
	'@ArthemFileUploadBundle/Resources/js/jquery-file-upload/jquery.fileupload-image.js'
	'@ArthemFileUploadBundle/Resources/js/arthem.fileupload.js'
	output='js/main.js' %}
	<script src="{{ asset_url }}"></script>
	{% endjavascripts %}
{% endblock %}
```

```django
{% block stylesheets %}
	...
	{% stylesheets
	...
	'@ArthemBaseBundle/Resources/css/sass/arthem.form.scss'
	'@ArthemFileUploadBundle/Resources/css/sass/file.scss'
	output='css/style.css' %}
	<link rel="stylesheet" href="{{ asset_url }}">
	{% endstylesheets %}
{% endblock %}
```

### Basic usage

In order to attach a file to an entity, you must set a ManyToOne (or a OneToOne) association on your entity.

Example:

```php
<?php
namespace Acme\DemoBundle\Entity;

use Arthem\Bundle\FileUploadBundle\Model\File;

class User
{
	/**
	 * @var File
	 * @ORM\ManyToOne(targetEntity="Arthem\Bundle\FileUploadBundle\Model\File", cascade={"persist", "remove"})
	 */
	protected $file;

	/**
	 * @param File $file
	 * @return $this
	 */
	public function setFile(File $file = null)
	{
		$this->file = $file;

		return $this;
	}

	/**
	 * @return File
	 */
	public function getFile()
	{
		return $this->file;
	}
}
```

### Image filter

Enable image module:

```yaml
arthem_file_upload:
    image:
        placeholders:
            Acme\DemoBundle\Entity\User:
                picture: "bundles/esuser/images/placeholder/user.png"
```

### Advanced model

If you wish to override the default file model, declare the new class in your configuration file:

```yaml
# app/config/config.yml

arthem_file_upload:
    file_class: Acme\DemoBundle\Entity\File
```

See also:
- [File downloader](downloader.md)
