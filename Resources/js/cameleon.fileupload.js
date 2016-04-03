window.Arthem = window.Arthem || {};

(function ($, Arthem) {
    "use strict";

    Arthem.fileUpload = {
        setup: function ($c, options) {
            var settings = $.extend({
                    multiple: false,
                    ajax: false,
                    crop: false,
                    crop_options: {},
                    preview_width: null,
                    preview_height: null,
                    token: null,
                    remove_file_label: "Remove",
                    unknown_error_message: "Sorry, an error has occured",
                    icon_classes: {},
                    target_selector: null,
                    pending_uploads_label: "Loading..."
                }, options),
                pendingUploads = 0,
                submitRequested = false,
                $id = $c.find("input[type=hidden]"),
                $form = $c.closest("form"),
                submitButtonUsed = null,
                originSubmitLabel;

            $form.find(":submit").on("click", function () {
                submitButtonUsed = $(this);
            });

            $form.submit(function () {
                var $submits = $(this).find(":submit");

                $submits.attr("disabled", true);

                if (0 < pendingUploads) {
                    $form.addClass("cn-pending-uploads");
                    originSubmitLabel = $submits.html();
                    $submits.each(function () {
                        $(this).data("origin-label", $(this).html());
                    });
                    $submits.html(settings.pending_uploads_label);
                    submitRequested = $(this);
                    return false;
                }
                // if a submit button was used to submit the form, is is now disabled and won't be transmitted to the server.
                // that's why we replace it with an hidden input, so the sf2 submit form type can determine if it was clicked or not
                if (null !== submitButtonUsed) {
                    var submitButtonReplacement = $('<input type="hidden">');
                    submitButtonReplacement.attr("name", submitButtonUsed.attr("name"));
                    $(this).append(submitButtonReplacement);
                }
            });

            $c.on("click", ".cn-remove-file-btn", function (e) {
                var data, id, index;
                e.preventDefault();
                if (settings.multiple) {
                    data = $id.val().split(",");
                    id = $(this).attr("data-file-id");
                    index = data.indexOf(id);
                    if (id) {
                        data.splice(index, 1);
                        $id.val(data.join(","));
                    }
                } else {
                    $id.val("");
                }
                var $preview = $(this).closest(".cn-file-preview");
                if ($preview.data("jqfileupload")) {
                    $preview.data("jqfileupload").abort();
                }
                $preview.remove();

                if (!settings.multiple) {
                    $c.find(".cn-fileupload-placeholder").show();
                }
            });

            $c.on("click", ".cn-fileupload-browse", function (e) {
                e.preventDefault();
                $(this).closest(".cn-fileupload").find("input[type=file]").trigger("click");
            });

            $c.find(".cn-image-crop").each(function () {
                var $crop = $(this);

                var opts = {
                    filter: settings.filter_name,
                    imageUrl: $crop.data("origin-src"),
                    imageId: $crop.data("file-id"),
                };
                if ($crop.data("coords")) {
                    var parts = $crop.data("coords").split(",");
                    opts.coords = {
                        width: parts[0],
                        height: parts[1],
                        top: parts[2],
                        left: parts[3]
                    }
                }
                Arthem.crop.init($crop, $.extend({}, settings.crop_options, opts));
                // Re-display .cn-progress because hidden by closeUpload() call
                $crop.find(".cn-crop-outer-btns, .cn-progress").show();
            });

            function updateProgress($progress, progress) {
                $progress.find(".progress-bar").css(
                    "width",
                    progress + "%"
                ).text(progress + "%");
                return $progress;
            }

            function closeUpload(context) {
                updateProgress(context.find(".cn-progress"), 0).hide();
                --pendingUploads;
                if (submitRequested && 0 === pendingUploads) {
                    $form.removeClass("cn-pending-uploads");
                    $form.find(":submit").each(function () {
                        $(this).html($(this).data("origin-label"));
                    });
                    submitRequested.submit();
                }
            }

            if (settings.ajax) {
                $c.find("input[type=file]").fileupload({
                    dataType: "json",
                    previewMaxWidth: settings.preview_width,
                    previewMaxHeight: settings.preview_height,
                    previewCrop: true,
                    url: settings.url,
                    formData: {
                        "file[_token]": settings.token,
                        "multiple": settings.multiple ? 1 : 0,
                        "filter_name": settings.filter_name,
                        "origin_filter_name": settings.origin_filter_name
                    },
                    paramName: "file[file][file]"
                })
                    .on("fileuploadadd", function (e, data) {
                        var $files = $c.find(".cn-files");

                        if (!settings.multiple) {
                            $c.find(".cn-fileupload-placeholder").hide();
                        }

                        if (null !== settings.target_selector) {
                            data.context = $(settings.target_selector);
                            data.context.find(".cn-fileupload-errors").html("");
                        } else {
                            if (!settings.multiple) {
                                $files.html("");
                            }
                            $.each(data.files, function (index, file) {
                                var $tmpl = $c.find(".cn-template .cn-file-preview").clone();
                                $tmpl.find(".cn-file-name").html(file.name);
                                $tmpl.data("jqfileupload", data);
                                data.context = $tmpl.appendTo($files);
                            });
                        }
                        data.submit();
                    })
                    .on("fileuploadprogress", function (e, data) {
                        var progress = parseInt(data.loaded / data.total * 100, 10);
                        updateProgress(data.context.find(".cn-progress"), progress);
                    })
                    .on("fileuploaddone", function (e, data) {
                        var $img,
                            file,
                            val;
                        data.context.find(".cn-progress .cn-progress-bar").css("width", "100%");
                        if (!settings.multiple) {
                            $id.val("");
                        }
                        file = data.result.file;
                        val = $id.val();
                        $id.val((val ? val + "," : "") + file.id);
                        data.context.find("a.cn-file-open-btn").attr("href", file.url);
                        data.context.find(".cn-remove-file a").attr("data-file-id", file.id);

                        closeUpload(data.context);

                        if (file.thumbnail_url) {
                            $img = $("<img/>", {
                                src: file.thumbnail_url
                            });
                            $img.load(function () {
                                if (settings.crop) {
                                    var $crop = data.context.find(".cn-image-crop");

                                    $crop.find(".cn-crop-area").html("").css("background-image", "url(" + file.thumbnail_url + ")");
                                    Arthem.crop.init($crop, $.extend({}, settings.crop_options, {
                                        filter: settings.filter_name,
                                        imageUrl: file.url,
                                        imageId: file.id
                                    }));
                                    // Re-display .cn-progress because hidden by closeUpload() call
                                    $crop.find(".cn-crop-outer-btns, .cn-progress").show();
                                } else {
                                    data.context.find(".cn-file-icon").html($img.hide().fadeIn());
                                }
                            });
                        }
                    })
                    .on("fileuploadfail", function (e, data) {
                        // We cancel submit on fail
                        var r;
                        submitRequested = false;
                        closeUpload(data.context);

                        if ("error" === data.textStatus) {
                            r = data.jqXHR.responseJSON;
                            if (r && r.errors) {
                                data.context.find(".cn-fileupload-errors").html(r.errors.join("<br>"));
                            } else {
                                data.context.find(".cn-fileupload-errors").html(settings.unknown_error_message);
                            }
                        }
                    })
                    .on("fileuploadsend", function () {
                        pendingUploads++;
                    })
                    .on("fileuploadprocessalways", function (e, data) {
                        var index = data.index,
                            i,
                            file = data.files[index],
                            node = $(data.context.children()[index]),
                            fileIconClass;
                        data.context.find(".cn-progress").show();
                        if (file.preview) {
                            node.find(".cn-file-icon").html(file.preview);
                        } else if (settings.icon_classes) {
                            if (settings.icon_classes[file.type]) {
                                fileIconClass = settings.icon_classes[file.type];
                            } else {
                                var r;
                                for (i in settings.icon_classes) {
                                    r = new RegExp("^" + i.replace('/', '\\/') + "$");
                                    if (r.test(file.type)) {
                                        fileIconClass = settings.icon_classes[i];
                                        break;
                                    }
                                }
                            }
                            node.find(".cn-file-icon").html($("<i/>", {
                                "class": fileIconClass
                            }));
                        }
                        if (file.error) {
                            node.find(".cn-fileupload-errors").html($("<div/>").text(file.error));
                        }
                    });
            }
        }
    };

    $.fn.arthemFileUpload = function (options) {
        Arthem.fileUpload.setup(this, options);
        return this;
    };
}(jQuery, window.Arthem));
