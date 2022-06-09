## NOTE:

This plugin won't do anything out of the box. It requires a developer to install certain packages to the server before it will work properly.

Also, please keep in mind, this is the first plugin I've ever created, made during lunch hours and off time. It is still in the very early stages of development and there's much to do. But my hope is that someday it will relieve all my the-client-uploaded-another-18MB-png-and-is-complaining-about-load-times woes.

## Setup
This plugin was built using [Spatie's PHP Image Optimizer](https://github.com/spatie/image-optimizer). The optimizer requires the following packages to work:

### jpegoptim
`sudo apt-get install jpegoptim`

### optipng
`sudo apt-get install optipng`

### pngquant
`sudo apt-get install pngquant`

### svgo
(as user web):
```bash
$: curl https://raw.githubusercontent.com/creationix/nvm/master/install.sh | bash
$: source ~/.profile
$: nvm install 10.13.0
$: npm install -g svgo@1.3.2
```

### gifsicle
`sudo apt-get install gifsicle`

### cwebp
`sudo apt-get install webp`

## Known issues

- The plugin can't detect the presence of svgo. It will always show a warning saying it isn't installed. I also haven't yet tested whether or not svg compression is actually working.

## Features that might hopefully come at some point eventually someday perhaps:

- Compression percentage option
- Database values tracking MB saved
- Using MB saved to calculate/estimate load time improvements
- Video compressor? https://github.com/PHP-FFMpeg/PHP-FFMpeg
- Ability to compress all images from given month (or all in general)
- Ability to compress individual images
