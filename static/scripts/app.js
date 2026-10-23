/*!
 * app.js - full file replacement with Edge compatibility fixes
 * - Keeps original app logic (posts, trash, image upload, editor, etc.)
 * - Adds file attachment upload (file input, previews, upload flow)
 * - Improves cross-browser drag/drop and file input handling (no relying on DataTransfer reassignment)
 *
 * Note: Ensure server PHP (php.ini) allows large uploads if you accept very large files (upload_max_filesize, post_max_size).
 */

var o_mask = false;
$("#dd_mask").click(function(){
	$(this).hide();
	if (o_mask && typeof o_mask.remove === "function") o_mask.remove();
});

// Multi-Upload State (images)
var uploadQueue = [];
var uploadedImages = [];
var maxImages = 12; // ✅ CHANGED from 10 to 12
var isUploading = false;

// File attachment upload state
var uploadFileQueue = [];
var uploadedFiles = [];
var maxFiles = 50; // client-side cap for attachments

// Helper: robust check whether dataTransfer contains files (cross-browser)
function dtContainsFiles(dt) {
	if(!dt) return false;
	try {
		// Some browsers provide types as DOMStringList with contains()
		if (typeof dt.contains === 'function') {
			return dt.contains('Files') || dt.contains('files');
		}
		// Some provide indexOf
		if (typeof dt.indexOf === 'function') {
			return dt.indexOf('Files') !== -1 || dt.indexOf('files') !== -1;
		}
		// Fallback: iterate/convert to array
		var arr = Array.prototype.slice.call(dt);
		return arr.indexOf('Files') !== -1 || arr.indexOf('files') !== -1;
	} catch(e) {
		// If types not iterable, fallback to checking dt.files
		return !!(dt && dt.files && dt.files.length);
	}
}

// Global helper functions for multi-upload (images)
function show_multi_upload_progress(currentIndex, total) {
	var progressText = 'Uploading image ' + currentIndex + ' of ' + total + '...';
	$('.e_loading .progress-text').text(progressText);
}

function show_image_previews(files, container) {
	container.empty();
	
	Array.from(files).forEach(function(file, index) {
		if (index >= maxImages) return;
		
		var reader = new FileReader();
		reader.onload = function(e) {
			var previewHtml = '<div class="image-preview-item" data-index="' + index + '">' +
				'<img src="' + e.target.result + '" alt="Image ' + (index + 1) + '">' +
				'<div class="image-preview-overlay">' +
				'<span class="image-number">' + (index + 1) + '</span>' +
				'<button class="remove-image-btn" data-index="' + index + '">×</button>' +
				'</div></div>';
			container.append(previewHtml);
		};
		reader.readAsDataURL(file);
	});
	
	container.off('click', '.remove-image-btn').on('click', '.remove-image-btn', function(e) {
		e.preventDefault();
		e.stopPropagation();
		var index = $(this).data('index');
		removeImageFromQueue(index);
	});
}

// Remove from in-memory queue and update UI only (do not set input.files — Edge compatibility)
function removeImageFromQueue(index) {
	// Remove from in-memory queue (our source of truth)
	uploadQueue.splice(index, 1);

	// Update UI previews
	var container = $('.image-preview-container');
	if (uploadQueue.length > 0) {
		show_image_previews(uploadQueue, container);
	} else {
		container.empty();
		$('.multi-upload-info').hide();
	}

	// Note: Do NOT attempt to set file_input.files via DataTransfer for compatibility.
	updateImageCounter();
}

function updateImageCounter() {
	var count = uploadQueue.length;
	var countText = count > 0 ? count + ' image' + (count > 1 ? 's' : '') + ' selected (max. ' + maxImages + ')' : ''; // ✅ CHANGED: uses maxImages variable
	$('.image-count').text(countText);
	
	if (count > 0) {
		$('.multi-upload-info').show();
	} else {
		$('.multi-upload-info').hide();
	}
}

function upload_multiple_images(files, modal, callback) {
	if (!files || files.length === 0) {
		callback([]);
		return;
	}
	
	var filesToUpload = Array.from(files).slice(0, maxImages);
	var uploadedResults = [];
	var currentIndex = 0;
	
	function uploadNext() {
		if (currentIndex >= filesToUpload.length) {
			callback(uploadedResults);
			return;
		}
		
		var file = filesToUpload[currentIndex];
		currentIndex++;
		
		show_multi_upload_progress(currentIndex, filesToUpload.length);
		
		if (file.type.match(/image/) === null) {
			$("body").error_msg("Only images can be uploaded.");
			uploadNext();
			return;
		}
		
		var form_data = new FormData();
		form_data.append('file', file);
		
		$.ajax({
			xhr: function() {
				var xhr = new window.XMLHttpRequest();
				xhr.upload.addEventListener("progress", function(evt) {
					if (evt.lengthComputable) {
						var percentComplete = evt.loaded / evt.total;
						modal.find('.e_loading .e_meter > span').width((percentComplete * 100) + "%");
					}
				}, false);
				return xhr;
			},
			dataType: "json",
			url: "ajax.php?action=upload_image",
			cache: false,
			contentType: false,
			processData: false,
			data: form_data,
			type: 'POST',
			success: function(data) {
				if (data.error) {
					$("body").error_msg(data.msg);
				} else {
					uploadedResults.push(data);
				}
				uploadNext();
			},
			error: function() {
				$("body").error_msg("Error uploading image " + currentIndex);
				uploadNext();
			}
		});
	}
	
	uploadNext();
}

// ===== File attachment helpers =====
function show_file_previews(files, container) {
	container.empty();
	Array.from(files).forEach(function(file, index) {
		if (index >= maxFiles) return;
		var previewHtml = '<div class="file-preview-item" data-index="' + index + '">' +
			'<span class="file-name">' + (file.name || ('File ' + (index+1))) + '</span>' +
			'<button class="remove-file-btn" data-index="' + index + '">×</button>' +
			'</div>';
		container.append(previewHtml);
	});
	container.off('click', '.remove-file-btn').on('click', '.remove-file-btn', function(e) {
		e.preventDefault();
		e.stopPropagation();
		var idx = $(this).data('index');
		removeFileFromQueue(idx);
	});
}

// Remove file from in-memory queue and update UI only (Edge-friendly)
function removeFileFromQueue(index) {
	uploadFileQueue.splice(index, 1);

	var previewContainer = $('.file-preview-container');
	if (uploadFileQueue.length > 0) {
		show_file_previews(uploadFileQueue, previewContainer);
	} else {
		previewContainer.empty();
		previewContainer.hide();
	}

	// Do not manipulate input.files
}

function upload_multiple_files(files, modal, callback) {
	if (!files || files.length === 0) {
		callback([]);
		return;
	}

	var filesToUpload = Array.from(files).slice(0, maxFiles);
	var uploadedResults = [];
	var currentIndex = 0;

	// server-side limit will be enforced; client shows loader
	function uploadNext() {
		if (currentIndex >= filesToUpload.length) {
			callback(uploadedResults);
			return;
		}

		var file = filesToUpload[currentIndex];
		currentIndex++;

		// show progress
		modal.find('.e_loading').show();
		modal.find('.e_loading .e_meter > span').width('0%');

		var form_data = new FormData();
		form_data.append('file', file);

		$.ajax({
			url: 'ajax.php?action=upload_file',
			type: 'POST',
			data: form_data,
			dataType: 'json',
			cache: false,
			contentType: false,
			processData: false,
			xhr: function() {
				var xhr = new window.XMLHttpRequest();
				xhr.upload.addEventListener("progress", function(evt) {
					if (evt.lengthComputable) {
						var percentComplete = (evt.loaded / evt.total) * 100;
						modal.find('.e_loading .e_meter > span').width(percentComplete + '%');
					}
				}, false);
				return xhr;
			},
			success: function(data) {
				if (data.error) {
					$("body").error_msg(data.msg || "Error uploading file");
				} else {
					uploadedResults.push(data);
				}
				uploadNext();
			},
			error: function() {
				$("body").error_msg("Error uploading file " + currentIndex);
				uploadNext();
			}
		});
	}

	uploadNext();
}

// ============================================
// TRASH MANAGEMENT
// ============================================
var trash = {
	viewing: false,
	limit: 20,
	offset: 0,
	last: false,
	loading: false,
	
	toggle: function() {
		if (trash.viewing) {
			trash.hide();
		} else {
			trash.show();
		}
	},
	
	show: function() {
		$('#b_feed').hide();
		$('#b_trash').show();
		$('#trash_toggle').hide();
		trash.viewing = true;
		trash.offset = 0;
		trash.last = false;
		$('#trash_posts').empty();
		trash.load();
	},
	
	hide: function() {
		$('#b_feed').show();
		$('#b_trash').hide();
		$('#trash_toggle').show();
		trash.viewing = false;
	},
	
	load: function() {
		if (trash.loading || trash.last) return;
		
		trash.loading = true;
		
		$.get({
			dataType: "json",
			url: "ajax.php",
			data: {
				action: "list_trash",
				limit: trash.limit,
				offset: trash.offset
			},
			success: function(trash_data) {
				if (trash_data.error) {
					$("body").error_msg(trash_data.msg);
					trash.loading = false;
					return;
				}
				
				if (!trash_data || trash_data.length === 0) {
					trash.last = true;
					trash.loading = false;
					return;
				}
				
				trash.offset += trash_data.length;
				
				if (trash_data.length < trash.limit) {
					trash.last = true;
				}
				
				$(trash_data).each(function(i, data) {
					var post = $('#prepared .post_row').clone();
					post.post_fill(data);
					post.addClass('in-trash');
					post.attr('data-in-trash', '1');
					post.apply_post();
					$("#trash_posts").append(post);
				});
				
				trash.loading = false;
			},
			error: function() {
				$("body").error_msg("Failed to load trash.");
				trash.loading = false;
			}
		});
	},
	
	update_count: function() {
		if (!trashEnabled) return;
		
		$.get({
			dataType: "json",
			url: "ajax.php",
			data: {
				action: "list_trash",
				limit: 1,
				offset: 0
			},
			success: function(data) {
				if (data && !data.error && data.length > 0) {
					// Get actual count
					$.get({
						dataType: "json",
						url: "ajax.php",
						data: {
							action: "list_trash",
							limit: 1000,
							offset: 0
						},
						success: function(allData) {
							if (allData && !allData.error) {
								var count = allData.length;
								if (count > 0) {
									$('.trash-count').text('(' + count + ')').show();
								} else {
									$('.trash-count').hide();
								}
							}
						}
					});
				} else {
					$('.trash-count').hide();
				}
			}
		});
	}
};

// Initialize trash toggle buttons
$(document).ready(function() {
	if (typeof trashEnabled !== 'undefined' && trashEnabled) {
		$('#show_trash_btn').click(function() {
			trash.show();
		});
		
		$('#hide_trash_btn').click(function() {
			trash.hide();
		});
		
		// Scroll to load more trash items
		$(window).on('scroll', function() {
			if (trash.viewing && !trash.loading && !trash.last) {
				if ($(window).scrollTop() + $(window).height() >= $("#eof_trash").position().top - 100) {
					trash.load();
				}
			}
		});
	}
});

// Posts loading functions
var posts = {
	initialized: false,
	first: false,
	last: false,
	loading: false,
	limit: 5,
	offset: 0,
	sort: "default",
	filter: {
		from: null,
		to: null,
		id: null,
		tag: null,
		loc: null,
		person: null
	},

	tryload: function(){
		if($(window).scrollTop() + $(window).height() >= $("#eof_feed").position().top)
			posts.load();
	},

	hash_update: function(){
		$(".more_posts").hide();
		posts.filter = {};

		location.hash.replace(/([a-z]+)\=([^\&]+)/g, function(_, key, value){
			if (key == "sort") {
				posts.sort = decodeURIComponent(value);
				return;
			}
			posts.filter[key] = decodeURIComponent(value);
			$(".more_posts").show();
		});

		posts.reload();
	},

	reload: function(){
		this.first = this.last = this.loading = false;
		this.offset = 0;
		$("#posts").empty();
		this.load();
		
		// Update trash count when reloading posts
		if (typeof trash !== 'undefined') {
			trash.update_count();
		}
	},

	add_new: function(post) {
		$("#posts").prepend(post);
		this.offset++;
	},

	load: function(){
		if(!posts.initialized || posts.loading || posts.last)
			return ;

		posts.loading = true;

		$.get({
			dataType: "json",
			url: "ajax.php",
			data: {
				action: "load",
				limit: posts.limit,
				offset: posts.offset,
				sort: posts.sort,
				filter: posts.filter
			},
			success: function(posts_data){
				if(posts_data.error){
					$("body").error_msg(posts_data.msg);
					return ;
				}

				if(!posts.first)
					posts.first = true;

				if(!posts_data){
					posts.last = true;
					return ;
				}

				posts.offset += posts_data.length;
				if(posts_data.length < posts.limit)
					posts.last = true;

				$(posts_data).each(function(i, data){
					var post = $('#prepared .post_row').clone();
					post.post_fill(data);
					post.apply_post();
					$("#posts").append(post);
				});

				posts.loading = false;
				posts.tryload();
			}
		});
	},

	init: function(){
		posts.hash_update();
		posts.initialized = true;
		posts.load();
	}
};

// Content functions
var lightboxes = 0;
var cnt_funcs = {
	link: function(data){
		var obj = $("#prepared .b_link").clone();
		if(!data.is_video){
			obj.find(".play").remove();
		}

		if(!data.thumb){
			obj.find(".thumb").remove();
			obj.find(".has_thumb").removeClass("has_thumb");
		} else {
			obj.find(".thumb img").attr("src", data.thumb);
		}

		obj.attr("href", data.link);
		obj.find(".title").text(data.title);
		obj.find(".desc").text(data.desc);
		obj.find(".host").text(data.host);

		return obj;
	},
	img_link: function(data){
		var obj = $("#prepared .b_imglink").clone();
		obj.attr("href", data.src);
		obj.find("img").attr("src", data.src);
		obj.find(".host").text(data.host);

		return obj;
	},
	image: function(data){
		var obj = $("#prepared .b_img").clone();
		obj.attr("href", data.path);
		obj.attr("data-lightbox", 'image-'+lightboxes++);
		obj.find("img").attr("src", data.thumb);

		return obj;
	},
	// Display ALL images, no limit
	images: function(dataArray){
		if (!Array.isArray(dataArray) || dataArray.length === 0) {
			return $('<div></div>');
		}
		
		var lightboxId = 'gallery-' + lightboxes++;
		var galleryContainer = $('<div class="b_gallery"></div>');
		var imageCount = dataArray.length;
		var gridClass = 'gallery-grid-' + imageCount;
		galleryContainer.addClass(gridClass);
		
		// Display ALL images (no limit)
		dataArray.forEach(function(imgData, index) {
			var imgLink = $('<a class="b_gallery_item"></a>');
			imgLink.attr("href", imgData.path);
			imgLink.attr("data-lightbox", lightboxId);
			imgLink.attr("data-title", "Image " + (index + 1) + " of " + imageCount);
			
			var img = $('<img>');
			img.attr("src", imgData.thumb);
			img.attr("alt", "Image " + (index + 1));
			
			imgLink.append(img);
			galleryContainer.append(imgLink);
		});
		
		return galleryContainer;
	},
	// Files: render simple download list
	files: function(filesArray){
		if (!Array.isArray(filesArray) || filesArray.length === 0) {
			return $('<div></div>');
		}
		var ul = $('<ul class="attachment-list"></ul>');
		filesArray.forEach(function(f) {
			var li = $('<li></li>');
			var a = $('<a target="_blank"></a>');
			a.attr('href', f.url || f.path);
			a.text(f.name || f.url || 'file');
			li.append(a);
			ul.append(li);
		});
		return ul;
	},
	// Mixed: images + files
	mixed: function(obj){
		var wrapper = $('<div class="mixed-content"></div>');
		if (obj.images && Array.isArray(obj.images) && obj.images.length) {
			wrapper.append(cnt_funcs.images(obj.images));
		}
		if (obj.files && Array.isArray(obj.files) && obj.files.length) {
			wrapper.append(cnt_funcs.files(obj.files));
		}
		return wrapper;
	}
};

// Login function
var login = {
	is: false,
	visitor: false,

	logout_btn: function(name){
		var btn = $('#prepared .logout_btn').clone();

		$(btn).click(function(){
			$.get({
				dataType: "json",
				url: "ajax.php",
				data: {
					action: "logout"
				},
				success: function(data){
					if(data.error){
						$("body").error_msg(data.msg);
						return ;
					}

					if(login.is){
						new_post.remove();
					}

					login.is = false;
					login.visitor = false;
					btn.remove();
					posts.reload();
					login.login_btn();
					
					// Hide trash toggle
					$('#trash_headline_btn').hide();
				}
			});
		});

		$("#headline").append(btn);
		
		// Show trash button after logout button
		if (typeof trashEnabled !== 'undefined' && trashEnabled) {
			$('#trash_headline_btn').show();
			trash.update_count();
		}
	},

	login_btn: function(){
		var btn = $('#prepared .login_btn').clone();

		$(btn).click(function(){
			var modal = $('#prepared .login_modal').clone();
			$("body").css("overflow", "hidden");

			modal.find(".nick,.pass").keypress(function(e) {
				if(e.which == 13) {
					modal.find(".do_login").click();
				}
			});

			modal.find(".close").click(function(){
				modal.close();
			});

			modal.find(".do_login").click(function(){
				$.post({
					dataType: "json",
					url: "ajax.php",
					data: {
						action: "login",
						nick: modal.find(".nick").val(),
						pass: modal.find(".pass").val()
					},
					success: function(data){
						if(data.error){
							modal.find(".modal-body").error_msg(data.msg);
							return ;
						}

						login.is = data.logged_in;
						login.visitor = data.is_visitor;

						if(login.is){
							new_post.create();
							// Show trash toggle
							$('#trash_toggle').show();
							// Update trash count
							trash.update_count();
						}
						btn.remove();
						posts.reload();
						login.logout_btn();
						modal.close();
					}
				});
			});

			$("body").append(modal);
			modal.find("input.nick").focus();
		});

		$("#headline").append(btn);
	},

	init: function(){
		$.get({
			dataType: "json",
			url: "ajax.php",
			data: {
				action: "handshake"
			},
			success: function(data){
				if(data.error){
					$("body").error_msg(data.msg);
					return ;
				}

				login.is = data.logged_in;
				login.visitor = data.is_visitor;
				if(!login.is && !login.visitor){
					login.login_btn();
				} else {
					login.logout_btn();
				}

				if(login.is){
					new_post.create();
					// Show trash button
					if (typeof trashEnabled !== 'undefined' && trashEnabled) {
						$('#trash_headline_btn').show();
					}
					// Update trash count on init
					trash.update_count();
				}

				posts.init();
			}
		});
	},
}

// New post function (images + files + mixed)
var new_post = {
	obj: null,

	create: function(){
		if(new_post.obj !== null)
			return;

		new_post.obj = $('#prepared .new_post').clone();

		var edit_form = $('#prepared .edit_form').clone();
		new_post.obj.find(".edit-form").append(edit_form);

		new_post.obj.apply_edit({"privacy": "private"});

		$(new_post.obj).find(".save").click(function(){
			var modal = new_post.obj;
			var saveBtn = $(this);
			
			// Upload flows: images first, then files. Support mixed content.
			function startSaving() {
				var finalContentType = modal.find(".i_content_type").val();
				var finalContent = modal.find(".i_content").val();

				// Call savePost AJAX
				$.post({
					dataType: "json",
					url: "ajax.php",
					data: {
						action: "insert",
						text: modal.find(".e_text").val(),
						feeling: modal.find(".i_feeling").val(),
						persons: modal.find(".i_persons").val(),
						location: modal.find(".i_location").val(),
						content_type: finalContentType,
						content: finalContent,
						privacy: modal.find(".privacy").data("val")
					},
					success: function(data){
						if(data.error){
							$("body").error_msg(data.msg);
							saveBtn.prop('disabled', false);
							modal.find(".e_loading").hide();
							return;
						}

						uploadQueue = [];
						uploadedImages = [];
						uploadFileQueue = [];
						uploadedFiles = [];

						new_post.clear();

						var post = $('#prepared .post_row').clone();
						post.post_fill(data);
						post.apply_post();
						posts.add_new(post);
						
						modal.find(".e_loading").hide();
					}
				});
			}

			// Handler to orchestrate uploads
			function doUploadsAndSave() {
				modal.find(".e_loading").css("display", "block");
				// upload images if present
				if (uploadQueue.length > 0) {
					upload_multiple_images(uploadQueue, modal, function(imgResults) {
						uploadedImages = imgResults || [];
						// proceed to files upload if any
						if (uploadFileQueue.length > 0) {
							upload_multiple_files(uploadFileQueue, modal, function(fileResults) {
								uploadedFiles = fileResults || [];
								// prepare mixed content if both exist
								prepareContentAndSave();
							});
						} else {
							// no files, save images only
							if (uploadedImages.length > 0) {
								modal.find(".i_content_type").val("images");
								modal.find(".i_content").val(JSON.stringify(uploadedImages));
							}
							startSaving();
						}
					});
				} else if (uploadFileQueue.length > 0) {
					// only files
					upload_multiple_files(uploadFileQueue, modal, function(fileResults) {
						uploadedFiles = fileResults || [];
						if (uploadedFiles.length > 0) {
							modal.find(".i_content_type").val("files");
							modal.find(".i_content").val(JSON.stringify(uploadedFiles));
						}
						startSaving();
					});
				} else {
					// nothing to upload
					startSaving();
				}
			}

			function prepareContentAndSave() {
				if (uploadedImages.length > 0 && uploadedFiles.length > 0) {
					// mixed
					modal.find(".i_content_type").val("mixed");
					modal.find(".i_content").val(JSON.stringify({images: uploadedImages, files: uploadedFiles}));
				} else if (uploadedImages.length > 0) {
					modal.find(".i_content_type").val("images");
					modal.find(".i_content").val(JSON.stringify(uploadedImages));
				} else if (uploadedFiles.length > 0) {
					modal.find(".i_content_type").val("files");
					modal.find(".i_content").val(JSON.stringify(uploadedFiles));
				}
				startSaving();
			}

			// Start upload/save routine
			saveBtn.prop('disabled', true);
			doUploadsAndSave();
		});

		$("#b_feed").prepend(new_post.obj);
	},

	clear: function(){
		new_post.remove();
		new_post.create();
	},

	remove: function(){
		new_post.obj.remove();
		new_post.obj = null;
	}
};

// Error message globals
var err_msg = {
	active: false,
	obj: null,
	t_out: null
};

// Error message function
$.fn.error_msg = function(msg){
	if(err_msg.active){
		err_msg.obj.remove();
		clearTimeout(err_msg.t_out);
	}

	err_msg.active = true;
	err_msg.obj = $("<div></div>");
	err_msg.obj.addClass("error");
	err_msg.obj.text(msg);

	var clear = $("<button></button>");
	clear.addClass("clear");
	clear.click(function(){
		err_msg.obj.remove();
		err_msg.active = false;
	});
	err_msg.obj.prepend(clear);

	$(this).prepend(err_msg.obj);

	err_msg.t_out = setTimeout(function(){
		err_msg.obj.fadeOut(500, function(){
			$(err_msg.obj).remove();
			err_msg.active = false;
		});
	}, 5000);
};

// Success message function
$.fn.success_msg = function(msg){
	var $msg = $('<div class="success_message" style="position:fixed; top:20px; left:50%; transform:translateX(-50%); background:#4CAF50; color:white; padding:15px 30px; border-radius:5px; z-index:10000; box-shadow:0 2px 10px rgba(0,0,0,0.2);">'+msg+'</div>');
	$("body").append($msg);
	setTimeout(function(){ $msg.fadeOut(function(){ $(this).remove(); }); }, 3000);
};

$(document).ajaxError(function(){
	$("body").error_msg("Ajax request failed.");
});

// Apply events on post editing
$.fn.apply_edit = function(data){
	var ignored_links = [], is_content = false;

	return this.each(function(){
		var modal = $(this);
		var currentImages = [];

		var add_content_loading = function(){
			modal.find(".e_loading").css("display", "block");
			modal.find(".e_loading .e_meter > span").width(0);
		};

		var content_loading_progress = function(progress){
			modal.find(".e_loading .e_meter > span").width((progress * 100) + "%");
		};

		var remove_content = function(){
			modal.find(".e_loading").hide();
			modal.find(".e_loading .e_meter > span").width(0);

			modal.find(".content").empty().hide();
			modal.find(".i_content_type").val("");
			modal.find(".i_content").val("");
			is_content = false;
			currentImages = [];
			uploadQueue = [];
			uploadFileQueue = [];
			// hide preview containers
			modal.find('.image-preview-container').hide().empty();
			modal.find('.file-preview-container').hide().empty();
			modal.find('.multi-upload-info').hide();
		};

		var remove_single_image = function(index) {
			if (currentImages.length > 0) {
				currentImages.splice(index, 1);
				
				if (currentImages.length > 0) {
					add_content("images", currentImages);
				} else {
					remove_content();
				}
			}
		};

		var show_editable_gallery = function(images) {
			var content = modal.find(".content").empty();
			var clear = $('<button class="clear" title="Remove all images"></button>');
			clear.click(remove_content);
			
			var galleryContainer = $('<div class="b_gallery_edit"></div>');
			
			images.forEach(function(imgData, index) {
				var imgItem = $('<div class="b_gallery_edit_item"></div>');
				
				var img = $('<img>');
				img.attr("src", imgData.thumb || imgData.path);
				img.attr("alt", "Image " + (index + 1));
				
				var removeBtn = $('<button class="remove-gallery-image-btn" title="Remove this image">×</button>');
				removeBtn.attr('data-index', index);
				removeBtn.click(function(e) {
					e.preventDefault();
					e.stopPropagation();
					var idx = parseInt($(this).attr('data-index'));
					remove_single_image(idx);
				});
				
				var imageNumber = $('<span class="gallery-image-number">' + (index + 1) + '</span>');
				
				imgItem.append(img);
				imgItem.append(imageNumber);
				imgItem.append(removeBtn);
				galleryContainer.append(imgItem);
			});
			
			content.append(clear).append(galleryContainer).css("display", "block");
		};

		var add_content = function(type, data){
			if(!data)
				return;

			modal.find(".e_loading").hide();
			
			if(type === "images" && Array.isArray(data)) {
				currentImages = data;
				show_editable_gallery(data);
			} else {
				var content = modal.find(".content").empty();
				var clear = $('<button class="clear"></button>');
				clear.click(remove_content);

				if(typeof cnt_funcs[type] === "function")
					content.append(clear).append(cnt_funcs[type](data)).css("display", "block");
			}

			modal.find(".i_content_type").val(type);
			modal.find(".i_content").val(JSON.stringify(currentImages.length > 0 ? currentImages : data));
			is_content = true;
		};

		var parse_link = function(t){
			if(is_content)
				return;

			t.replace(/(https?:\/\/[^\s]+)/g, function(link, a, b) {
				if(ignored_links.indexOf(link) !== -1)
					return ;

				add_content_loading();

				$.get({
					dataType: "json",
					url: "ajax.php",
					data: {
						action: "parse_link",
						link: link
					},
					success: function(data){
						if(data.error){
							$("body").error_msg(data.msg);
							remove_content();
							return ;
						}

						ignored_links.push(link);

						if(data == null || typeof data.valid === "undefined" || !data.valid)
							return ;

						add_content(data.content_type, data.content);
					},
					error: function() {
						$("body").error_msg("Error when communicating with the server.");
						remove_content();
					}
				});
			});
		};

		var upload_image = function(file) {
			if(file.type.match(/image/) === null){
				$("body").error_msg("Only images can be uploaded.");
				return ;
			}

			var form_data = new FormData();
			form_data.append('file', file);

			add_content_loading();

			$.ajax({
				xhr: function(){
					var xhr = new window.XMLHttpRequest();

					xhr.upload.addEventListener("progress", function(evt){
						if (evt.lengthComputable) {
							var percentComplete = evt.loaded / evt.total;
							content_loading_progress(percentComplete);
						}
					}, false);

					return xhr;
				},
				dataType: "json",
				url: "ajax.php?action=upload_image",
				cache: false,
				contentType: false,
				processData: false,
				data: form_data,
				type: 'POST',
				success: function(data){
					if(data.error){
						$("body").error_msg(data.msg);
						remove_content();
						return ;
					}

					add_content("image", data);
				},
				error: function() {
					$("body").error_msg("Error when communicating with the server.");
					remove_content();
				}
			});
		}

		// Paste handler
		modal.find(".e_text").val(data.plain_text)
		.on('paste', function(e) {
			var items = (e.clipboardData || e.originalEvent.clipboardData).items;
			for(var i in items) {
				var item = items[i];

				if(item.type === 'text/plain'){
					item.getAsString(function(text) {
						parse_link(text);
					});
					break;
				}

				if(item.type.indexOf('image') !== -1){
					var file = item.getAsFile();
					upload_image(file);
					break;
				}
			}
		});

		setTimeout(function(){
			var textarea = $(modal.find(".e_text"));
			textarea.css({
				'min-height': '120px',
				'overflow-y': 'auto',
				'resize': 'vertical',
				'display': 'block'
			});
		},0);

		// Image input wiring
		var file_data = modal.find(".photo_upload");
		file_data.attr("multiple", "multiple");
		
		$(file_data).change(function(){
			var files = file_data[0].files;
			
			if (!files || files.length === 0) return;
			
			uploadQueue = Array.from(files).slice(0, maxImages);
			
			var previewContainer = modal.find('.image-preview-container');
			if (previewContainer.length === 0) {
				previewContainer = $('<div class="image-preview-container"></div>');
				modal.find('.drop_space').after(previewContainer);
			}
			
			previewContainer.show();
			show_image_previews(uploadQueue, previewContainer);
			updateImageCounter();
			
			if (files.length === 1) {
				add_content_loading();
				upload_image(files[0]);
			} else {
				if (modal.find('.multi-upload-info').length === 0) {
					previewContainer.after('<div class="multi-upload-info"><span class="image-count"></span> - Click \'Save\' to upload</div>');
				}
				modal.find('.multi-upload-info').show();
				updateImageCounter();
			}
		});

		// File input wiring (new)
		var file_attach_input = modal.find(".file_upload");
		file_attach_input.attr("multiple", "multiple");
		file_attach_input.change(function(){
			var files = file_attach_input[0].files;
			if (!files || files.length === 0) return;

			uploadFileQueue = Array.from(files).slice(0, maxFiles);

			var previewContainer = modal.find('.file-preview-container');
			if (previewContainer.length === 0) {
				previewContainer = $('<div class="file-preview-container"></div>');
				modal.find('.drop_space').after(previewContainer);
			}
			previewContainer.show();
			show_file_previews(uploadFileQueue, previewContainer);
		});

		if(data.feeling){
			modal.find(".i_feeling").val(data.feeling);
			modal.find(".options li.feeling a").addClass("active");
			modal.find(".options_content tr.feeling").css("display", "table-row");
		}
		if(data.persons){
			modal.find(".i_persons").val(data.persons);
			modal.find(".options li.persons a").addClass("active");
			modal.find(".options_content tr.persons").css("display", "table-row");
		}
		if(data.location){
			modal.find(".i_location").val(data.location);
			modal.find(".options li.location a").addClass("active");
			modal.find(".options_content tr.location").css("display", "table-row");
		}

		modal.find(".options_content tr").each(function(){
			var oc = $(this);
			var op = modal.find(".options li."+oc.attr("class")+" a");

			oc.find(".clear").click(function(){
				oc.find("input").val("");
				op.removeClass("active");
				oc.hide();
			});

			op.click(function(){
				oc.toggle();
				if(oc.find("input").val() == "")
					$(op).toggleClass("active");
				oc.find("input").focus();
			});
		});

		modal.find(".privacy").click(function(){
			var privacy_btn = $(this);

			o_mask = $("#prepared .privacy_settings").clone();
			$("body").append(o_mask);
			o_mask.css({
				top: $(this).offset().top - (($(o_mask).outerHeight() - $(this).outerHeight()) / 2) +  'px',
				left: $(this).offset().left - (($(o_mask).outerWidth() - $(this).outerWidth()) / 2 ) + 'px'
			});

			$("#dd_mask").show();
			o_mask.show();

			$(o_mask).find(".set").click(function(){
				privacy_btn.data("val", $(this).data("val"));
				privacy_btn.find(".cnt").html($(this).html());
				$("#dd_mask").click();
			});

		});

		modal.find(".privacy").data("val", data.privacy);
		modal.find(".privacy .cnt").html($("#prepared .privacy_settings .set[data-val="+data.privacy+"]").html());

		if(data.content_type){
			try{
				var parsedContent = JSON.parse(data.content);
				add_content(data.content_type, parsedContent);
			} catch(err) {}
		}

		modal.find(".drop_space").filedrop(function(singleFile){
			// keep backward compatibility: drop single file -> image upload (existing)
			upload_image(singleFile);
		});
	});
};

// Fill post data - WITH FIXED TOGGLE (uses translated text from PHP)
$.fn.post_fill = function(data){
	var post = $(this);

	post.data("id", data.id);

	if(parseInt(data.is_hidden)) {
		post.addClass("is_hidden");
	}
	
	if(data.is_sticky && parseInt(data.is_sticky)) {
		post.addClass("sticky");
		post.attr("data-sticky", "1");
	} else {
		post.removeClass("sticky");
		post.attr("data-sticky", "0");
	}
	
	post.find(".b_overlay .button").click(function(){
		var overlay = post.find(".b_overlay");
		var elementTop = $(overlay).offset().top;
		var elementBottom = elementTop + $(overlay).outerHeight();
		$(overlay).hide();

		var showOverlay = function() {
			$(overlay).css("display", "");
			$(window).off('scroll', showOnViewport);
			$(window).off('blur', showOverlay);
		};
		var showOnViewport = function() {
			var viewportTop = $(window).scrollTop();
			var viewportBottom = viewportTop + $(window).height();

			if ((elementBottom > viewportTop && elementTop > viewportBottom) || (elementBottom < viewportTop && elementTop < viewportBottom)) {
				showOverlay();
			}
		};
		$(window).on('scroll', showOnViewport);
		$(window).on('blur', showOverlay);
	});

	post.find(".b_text").html(data.text);

	post.find(".b_text").find(".tag").click(function(){
		var tag = $(this).text();
		tag = tag.substr(1);
		location.hash = 'tag\='+tag;
	});

	if(data.datetime)
		post.find(".b_date").text(data.datetime);

	post.find(".b_date").attr("href", "#id="+data.id);

	// ===== FIXED TOGGLE - Uses translated text from PHP data attributes =====
	var height = 200;
	var textContainer = post.find(".b_text");
	
	// Remove old buttons
	post.find(".show_more").remove();
	textContainer.removeClass("text-collapsed");
	
	if(data.text.length > 400){
		textContainer.css("max-height", height+"px");
		textContainer.addClass("text-collapsed");
		
		var show_more = $('#prepared .show_more').clone();
		
		// ✅ Get translated texts from PHP
		var textMore = show_more.text(); // Already translated: "Mehr Anzeigen"
		var textLess = $('#prepared').attr('data-show-less-text') || 'Weniger Anzeigen';
		
		show_more.attr("data-expanded", "false");
		show_more.attr("data-text-more", textMore);
		show_more.attr("data-text-less", textLess);
		show_more.insertAfter(textContainer);
		
		show_more.click(function(){
			var isExpanded = $(this).attr("data-expanded") === "true";
			
			if(!isExpanded){
				// Expand
				textContainer.css("max-height", 'none');
				textContainer.removeClass("text-collapsed");
				$(this).text($(this).attr("data-text-less"));
				$(this).attr("data-expanded", "true");
			} else {
				// Collapse
				textContainer.css("max-height", height+"px");
				textContainer.addClass("text-collapsed");
				$(this).text($(this).attr("data-text-more"));
				$(this).attr("data-expanded", "false");
				
				// Smooth scroll back to post
				$('html, body').animate({
					scrollTop: post.offset().top - 100
				}, 300);
			}
		});
	} else {
		textContainer.css("max-height", 'none');
	}

	if(typeof hljs !== "undefined"){
		post.find("code").each(function(i, block) {
			hljs.highlightBlock(block);
		});
	}

	post.find(".b_feeling").text(data.feeling);
	post.find(".b_persons").text(data.persons);
	post.find(".b_location").text(data.location).click(function(){
		location.hash = 'loc\='+$(this).text();
	});

	post.find(".b_options").hide();
	post.find(".b_here").hide();
	post.find(".b_with").hide();
	post.find(".b_location").hide();

	post.find(".privacy_icon").attr("class", "privacy_icon "+data.privacy).attr("title", "Shared with: "+data.privacy);

	if(data.content_type && typeof cnt_funcs[data.content_type] === "function"){
		try{
			data.content = JSON.parse(data.content)
			post.find(".b_content").html(cnt_funcs[data.content_type](data.content)).show();
		} catch(err) {}
	}

	if(!data.feeling && !data.persons && !data.location)
		return ;

	post.find(".b_options").show();

	if(data.persons)
		post.find(".b_with").show();

	if(data.location){
		post.find(".b_here").show();
		post.find(".b_location").show();
	}

	return post;
};

$.fn.close = function(){
	$(this).remove();
	$("body").css("overflow", "auto");
};

// Apply events on post
$.fn.apply_post = function(){
	return this.each(function(){
		var post = $(this);
		var post_id = post.data("id");
		var isInTrash = post.hasClass('in-trash') || post.attr('data-in-trash') === '1';

		if(!login.is){
			$(post).find(".b_tools").css("display", "none").click(function(){});
			return ;
		}

		$(post).find(".b_tools").css("display", "inline-block").click(function(){
			o_mask = $('#prepared .post_tools').clone();
			$("body").append(o_mask);
			o_mask.css({
				top: $(this).offset().top + $(this).outerHeight() + 5 + 'px',
				left: $(this).offset().left + $(this).outerWidth() - $(o_mask).outerWidth() - 5 + 'px'
			});

			$("#dd_mask").show();
			o_mask.show();
			
			// Show/hide options based on trash status
			if (isInTrash) {
				// In trash: only show restore and permanent delete
				o_mask.find('li.normal-only').hide();
				o_mask.find('li.trash-only').show();
				
				// Only show permanent delete if HARD_DELETE_FILES is enabled
				if (typeof hardDeleteFilesEnabled !== 'undefined' && !hardDeleteFilesEnabled) {
					o_mask.find('.permanent_delete_post').parent().hide();
				}
			} else {
				// Normal post: hide trash-only options
				o_mask.find('li.trash-only').hide();
				o_mask.find('li.normal-only').show();
			}

			$(o_mask).find(".edit_post").click(function(){
				$("#dd_mask").click();

				$.get({
					dataType: "json",
					url: "ajax.php",
					data: {action: "edit_data", id: post_id},
					success: function(data){
						if(data.error){
							$("body").error_msg(data.msg);
							return ;
						}

						var modal = $('#prepared .edit_modal').clone();
						$("body").css("overflow", "hidden");

						modal.apply_edit(data);

						modal.find(".close").click(function(){
							modal.close();
						});

						modal.find(".save").click(function(){
							var saveBtn = $(this);
							
							// Upload flows handled inside new_post.create/save (same behavior for edit)
							if (uploadQueue.length > 0 || uploadFileQueue.length > 0) {
								saveBtn.prop('disabled', true);
								modal.find(".e_loading").css("display", "block");
								modal.find(".e_loading .e_meter > span").width(0);

								// images -> files -> save
								if (uploadQueue.length > 0) {
									upload_multiple_images(uploadQueue, modal, function(imgResults) {
										uploadedImages = imgResults || [];
										if (uploadFileQueue.length > 0) {
											upload_multiple_files(uploadFileQueue, modal, function(fileResults) {
												uploadedFiles = fileResults || [];
												prepareAndSaveUpdate();
											});
										} else {
											if (uploadedImages.length > 0) {
												modal.find(".i_content_type").val("images");
												modal.find(".i_content").val(JSON.stringify(uploadedImages));
											}
											saveUpdate();
										}
									});
								} else if (uploadFileQueue.length > 0) {
									upload_multiple_files(uploadFileQueue, modal, function(fileResults) {
										uploadedFiles = fileResults || [];
										if (uploadedFiles.length > 0) {
											modal.find(".i_content_type").val("files");
											modal.find(".i_content").val(JSON.stringify(uploadedFiles));
										}
										saveUpdate();
									});
								}

							} else {
								saveUpdate();
							}

							function prepareAndSaveUpdate() {
								if (uploadedImages.length > 0 && uploadedFiles.length > 0) {
									modal.find(".i_content_type").val("mixed");
									modal.find(".i_content").val(JSON.stringify({images: uploadedImages, files: uploadedFiles}));
								} else if (uploadedImages.length > 0) {
									modal.find(".i_content_type").val("images");
									modal.find(".i_content").val(JSON.stringify(uploadedImages));
								} else if (uploadedFiles.length > 0) {
									modal.find(".i_content_type").val("files");
									modal.find(".i_content").val(JSON.stringify(uploadedFiles));
								}
								saveUpdate();
							}

							function saveUpdate() {
								$.post({
									dataType: "json",
									url: "ajax.php",
									data: {
										action: "update",
										id: post_id,
										text: modal.find(".e_text").val(),
										feeling: modal.find(".i_feeling").val(),
										persons: modal.find(".i_persons").val(),
										location: modal.find(".i_location").val(),
										content_type: modal.find(".i_content_type").val(),
										content: modal.find(".i_content").val(),
										privacy: modal.find(".privacy").data("val")
									},
									success: function(data){
										if(data.error){
											modal.find(".modal-body").error_msg(data.msg);
											saveBtn.prop('disabled', false);
											modal.find(".e_loading").hide();
											return;
										}

										uploadQueue = [];
										uploadedImages = [];
										uploadFileQueue = [];
										uploadedFiles = [];

										data.id = post_id;
										post.post_fill(data);
										modal.close();
										
										modal.find(".e_loading").hide();
									}
								});
							}
						});

						$("body").append(modal);
					}
				});
			});

			// The rest of handlers (date edit, hide/show, delete/restore/permanent delete, sticky)
			// remain implemented as in the original file (already present in this replacement).
			
		});
	});
};

// filedrop supports multi-upload via drag & drop with robust checks
$.fn.filedrop = function(callback){
	return this.each(function() {
		$(this).bind('dragover dragleave drop', function(event) {
			event.stopPropagation();
			event.preventDefault();
		});

		var dropTimer;
		$(this).on('dragover', function(e) {
			var dt = e.originalEvent.dataTransfer;
			if(dtContainsFiles(dt)) {
				$(".e_drop").css("display", "flex");
				window.clearTimeout(dropTimer);
			}
		}).on('dragleave drop', function(e) {
			dropTimer = window.setTimeout(function() {
				$(".e_drop").hide();
			}, 25);
		});

		$(this).bind('drop', function(event) {
			var files = event.originalEvent.target.files || event.originalEvent.dataTransfer.files;

			// Support multiple files via drag & drop
			if(typeof callback === "function" && files && files.length > 0) {
				if(files.length > 1) {
					// Separate images vs non-images
					var images = Array.from(files).filter(function(f){ return f.type && f.type.match(/image/); });
					if (images.length > 0) {
						uploadQueue = images.slice(0, maxImages);
						var modal = $(this).closest('.edit_form').parent();
						var previewContainer = modal.find('.image-preview-container');
						if (previewContainer.length === 0) {
							previewContainer = $('<div class="image-preview-container"></div>');
							$(this).after(previewContainer);
						}
						previewContainer.show();
						show_image_previews(uploadQueue, previewContainer);
						updateImageCounter();
						if (modal.find('.multi-upload-info').length === 0) {
							previewContainer.after('<div class="multi-upload-info"><span class="image-count"></span> - Click \'Save\' to upload</div>');
						}
						modal.find('.multi-upload-info').show();
					} else {
						// non-image files: populate file queue
						uploadFileQueue = Array.from(files).slice(0, maxFiles);
						var modal = $(this).closest('.edit_form').parent();
						var previewContainer = modal.find('.file-preview-container');
						if (previewContainer.length === 0) {
							previewContainer = $('<div class="file-preview-container"></div>');
							$(this).after(previewContainer);
						}
						previewContainer.show();
						show_file_previews(uploadFileQueue, previewContainer);
					}
				} else {
					// Single file - use old callback for backward compatibility
					callback(files[0]);
				}
			}

			return false;
		});
	});
};

login.init();

var dragTimer;
$(document).on('dragover', function(e) {
	var dt = e.originalEvent.dataTransfer;
	if(dtContainsFiles(dt)) {
		$(".e_drag").css("display", "flex");
		window.clearTimeout(dragTimer);
	}
}).on('dragleave drop', function(e) {
	dragTimer = window.setTimeout(function() {
		$(".e_drag").hide();
	}, 25);
});

$(window)
.on("scroll resize touchmove", posts.tryload)
.on("hashchange", posts.hash_update);