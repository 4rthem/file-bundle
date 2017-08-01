const CROP = require('./crop');

const arthemCrop = {
    init: function ($c, options) {
        var settings = $.extend({
            imageUrl: null,
            padding: 0,
            coords: {},
            modal: false,
            cropOutertBtnsSelector: '.cn-crop-outer-btns',
            croppingClass: 'cn-cropping',
            upload: false,
            uploadBtnSelector: '.cn-upload-btn',
            saveBtnSelector: '.cn-save-btn',
            cancelBtnSelector: '.cn-cancel-btn',
            sliderSelector: '.cn-crop-slider',
            areaSelector: '.cn-crop-area',
            actionsSelector: '.cn-crop-actions',
            resultSelector: null,
            cropUrl: null,
            imageId: null,
            filter: 'small',
            originFilter: 'large',
            progress: true,
            progressClass: 'progress progress-striped active',
            progressBarClass: 'progress-bar',
            progressContainerSelector: '.cn-progress',
            deleteUrl: null,
            deleteConfirmText: 'Are you sure?',
            deleteBtnSelector: '.cn-remove-btn',
            placeholderUrl: null,
            animationSpeed: 100
        }, options);

        var coords,
            $progress,
            modal = settings.modal ? $c.find('.modal').eq(0) : null,
            $actions = $c.find(settings.actionsSelector),
            $cropOuterBtns = $c.find(settings.cropOutertBtnsSelector),
            $saveBtn = $c.find(settings.saveBtnSelector),
            $cancelBtn = $c.find(settings.cancelBtnSelector),
            $area = $c.find(settings.areaSelector),
            $progressContainer = $c.find(settings.progressContainerSelector),
            $slider = $c.find(settings.sliderSelector);

        if (!settings.imageId) {
            $cropOuterBtns.hide();
        }

        function initCoord(c) {
            coords = $.extend({
                left: 0,
                top: 0,
                width: null,
                height: null
            }, c || {});
        }

        function imgPromise(url, callback) {
            $('<img/>', {
                src: url,
                load: callback
            });
        }

        initCoord(options.coords);

        if (!settings.resultSelector) {
            settings.resultSelector = settings.areaSelector;
        }

        function createProgressBar(progression) {
            var $progressDiv = $('<div/>', {
                'class': settings.progressClass
            });
            $('<div/>', {
                'class': settings.progressBarClass,
                role: 'progressbar',
                html: progression ? '0%' : '',
                width: progression ? '0%' : '100%'
            }).appendTo($progressDiv);
            $progressDiv.appendTo($progressContainer);
            return $progressDiv;
        }

        function displayProgressBar() {
            if (settings.progress && !$progress) {
                $progress = createProgressBar();
            }
        }

        function removeProgressBar() {
            if (settings.progress && $progress) {
                $progress.remove();
                $progress = null;
            }
        }

        function refreshImage(url, data, callback) {
            displayProgressBar();
            var $i = $c.find(settings.resultSelector);

            imgPromise(url, function () {
                removeProgressBar();
                if (callback) {
                    callback.call();
                    if (!settings.modal) {
                        $i.css({backgroundImage: 'url(' + url + ')'});
                        return;
                    }
                }
                $i.animate({opacity: 0}, settings.animationSpeed, function () {
                    if ($i.is('img')) {
                        $i.attr('src', url);
                        $i.load(function () {
                            $i.animate({opacity: 1}, settings.animationSpeed);
                        });
                    } else {
                        $i.animate({opacity: 0}, settings.animationSpeed, function () {
                            $i.css({backgroundImage: 'url(' + url + ')'});
                            $i.animate({opacity: 1}, settings.animationSpeed);
                        });
                    }
                });
            });
        }

        function turnOff() {
            $cropOuterBtns.show();
            $actions.hide();


            $c.find('.cn-remove-file').show();

            removeProgressBar();

            $area.find('.cropMain').remove();
            $c.removeClass(settings.croppingClass);
            $saveBtn.unbind('click');
            $slider.html('');
            if (settings.modal) {
                modal.modal('hide');
            }
        }

        if (settings.upload) {
            var $fileInput = $('<input/>', {
                    'type': 'file',
                    'accept': 'image/jpeg,image/png,image/gif'
                }),
                uploadOptions = $.extend(true, {
                    paramName: 'file[file][file]',
                    dataType: 'json',
                    url: null,
                    previewMaxWidth: $area.width(),
                    previewMaxHeight: $area.height(),
                    previewCrop: true,
                    formData: {
                        filter_name: settings.filter,
                        origin_filter_name: settings.originFilter
                    },
                    replaceFileInput: false
                }, settings.upload);

            $fileInput
                .fileupload(uploadOptions)
                .on('fileuploadadd', function (e, data) {
                    turnOff();
                    if (settings.progress) {
                        data.$progress = createProgressBar(true);
                    }
                    data.submit();
                })
                .on('fileuploaddone', function (e, data) {
                    if (data.result.file) {
                        var $parent = $c.find(settings.resultSelector).parent();
                        settings.imageUrl = data.result.file.url;
                        refreshImage(data.result.file.thumbnail_url, data, function () {
                            $area.html('');
                        });
                        if ($parent.is('a')) {
                            $parent.attr('href', data.result.file.url);
                        }
                        initCoord();
                        settings.imageId = data.result.file.id;

                        $cropOuterBtns.show();

                        if (settings.deleteBtnSelector) {
                            $c.find(settings.deleteBtnSelector).show();
                        }
                    } else if (data.result.errors) {
                        console.log(data.result.errors.join(', '));
                    }
                    if (settings.progress) {
                        data.$progress.remove();
                    }
                })
                .on('fileuploadfail', function (e, data) {
                    alert('Error');
                    if (settings.progress) {
                        data.$progress.remove();
                    }
                })
                .on('fileuploadprogress', function (e, data) {
                    var progress = parseInt(data.loaded / data.total * 100, 10);
                    data.$progress.find('.' + settings.progressBarClass).css(
                        'width',
                        progress + '%'
                    ).text(progress + '%');
                })
                .on('fileuploadprocessalways', function (e, data) {
                    if (data.files[0].preview) {
                        $area.html(data.files[0].preview);
                    }
                });

            $c.find(settings.uploadBtnSelector).click(function (e) {
                $fileInput.trigger('click');
            });
        }

        function turnOn() {
            if (!settings.imageId) {
                return;
            }
            if (settings.modal) {
                modal.modal('show');
            }
            $c.trigger({
                type: 'crop-preload'
            });

            $c.find('.cn-remove-file').hide();

            var timeout = setTimeout(function () {
                displayProgressBar();
            }, 200);

            imgPromise(settings.imageUrl, function () {
                if (timeout) {
                    clearTimeout(timeout);
                }

                removeProgressBar();
            });

            $cropOuterBtns.hide();
            $actions.show();

            $('<div class="cropMain"></div>').prependTo($area)
                .width($area.width())
                .height($area.height());
            $c.addClass(settings.croppingClass);
            var crop = new CROP({
                padding: settings.padding,
                slider: $('<div>').appendTo($slider)
            });

            $saveBtn.click(function () {
                displayProgressBar();
                $actions.hide();
                $.ajax({
                    url: settings.cropUrl,
                    type: 'POST',
                    data: {
                        crop: crop.coordinates(),
                        filter: settings.filter,
                        origin_filter: settings.originFilter,
                        id: settings.imageId
                    },
                    success: function (resp) {
                        if (resp.url) {
                            refreshImage(resp.url, null, function () {
                                turnOff();
                            });
                        } else {
                            turnOff();
                        }
                        const c = resp.crop;
                        coords.left = c.l;
                        coords.top = c.t;
                        if (c.w) {
                            coords.width = c.w;
                        }
                        if (c.h) {
                            coords.height = c.h;
                        }
                    },
                    error: function () {
                        turnOff();
                    }
                });
            });
            crop.init($area.find('.cropMain'));
            crop.loadImg(settings.imageUrl, {
                l: coords.left,
                t: coords.top,
                w: coords.width,
                h: coords.height
            });
        }

        if (settings.deleteUrl) {
            $c.find(settings.deleteBtnSelector).click(function (e) {
                e.preventDefault();
                if (settings.imageId && confirm(settings.deleteConfirmText)) {
                    $.ajax({
                        url: settings.deleteUrl,
                        type: 'DELETE',
                        data: {
                            id: settings.imageId
                        },
                        success: function () {
                            settings.imageId = null;
                            settings.imageUrl = null;
                            initCoord();
                            refreshImage(settings.placeholderUrl);
                            turnOff();
                        }
                    });
                }
            });
        }

        $cropOuterBtns.find(".cn-crop-btn").click(function (e) {
            e.preventDefault();
            turnOn();
        });
        $cancelBtn.click(function (e) {
            e.preventDefault();
            turnOff();
        });
    }
};

$.fn.arthemCrop = function (options) {
    return this.each(function () {
        crop.init($(this), options);
    });
};

module.exports = arthemCrop;
