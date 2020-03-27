var o_mask = false;
$("#dd_mask").click(function(){
	$(this).hide();
	o_mask.remove();
});

// Posts loading functions
var posts = {
	initialized: false, // Is initialized?
	first: false,       // Is first loaded?
	last: false,        // Is last loaded?
	loading: false,     // Is something loading right now?

	limit: 5,           // Limit posts per load
	offset: 0,          // Current offset

	filter: {
		from: null,     // Show posts from specified date
		to: null,       // Show posts to specified date
		id: null,       // Show only one post with specified id
		tag: null,      // Show posts that contains specified tag 
		loc: null,      // Show posts that location contains specified location 
		person: null    // Show posts that person contains specified person 
	},

	tryload: function(){
		if($(window).scrollTop() + $(window).height() >= $("#eof_feed").position().top)
			posts.load();
	},

	hash_update: function(){
		$(".more_posts").hide();
		posts.filter = {};

		// Update ID hash
		location.hash.replace(/([a-z]+)\=([^\&]+)/g, function(_, key, value){
			posts.filter[key] = decodeURIComponent(value);
			$(".more_posts").show();
		});

		posts.reload();
	},

	reload: function(){
		// Reset values
		this.first = this.last = this.loading = false;
		this.offset = 0;

		// Remove current and load new
		$("#posts").empty();
		this.load();
	},

	add_new: function(post) {
		$("#posts").prepend(post);
		this.offset++;
	},

	load: function(){
		// If is something loading now or is loading done
		if(!posts.initialized || posts.loading || posts.last)
			return ;

		// Now is
		posts.loading = true;

		// Load
		$.get({
			dataType: "json",
			url: "ajax.php",
			data: {
				action: "load",
				limit: posts.limit,
				offset: posts.offset,
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
					// Create empty post
					var post = $('#prepared .post_row').clone();

					// Update post data and apply scripts
					post.post_fill(data);
					post.apply_post();

					// Prepend
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
	}
};

// Login function
var login = {
	is: false,
	visitor: false,

	// Logout button
	logout_btn: function(name){
		var btn = $('#prepared .logout_btn').clone();

		// Onclick show modal
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

					// Remove new post input
					if(login.is){
						new_post.remove();
					}

					// Is not logged in anymore
					login.is = false;
					login.visitor = false;
					// Remove logout button
					btn.remove();
					// Load first posts
					posts.reload();
					// Append login button
					login.login_btn();
				}
			});
		});

		$("#headline").append(btn);
	},

	// Login button
	login_btn: function(){
		var btn = $('#prepared .login_btn').clone();

		// Onclick show modal
		$(btn).click(function(){
			// Clone modal
			var modal = $('#prepared .login_modal').clone();
			$("body").css("overflow", "hidden");

			// On enter save
			modal.find(".nick,.pass").keypress(function(e) {
				if(e.which == 13) {
					modal.find(".do_login").click();
				}
			});

			// On close
			modal.find(".close").click(function(){
				modal.close();
			});

			// On save
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

						// Now is logged in
						login.is = data.logged_in;
						login.visitor = data.is_visitor;

						// Logged in user can add post
						if(login.is){
							new_post.create();
						}
						// Remove login button
						btn.remove();
						// Load first posts
						posts.reload();
						// Append logout btn
						login.logout_btn();
						// Close modal
						modal.close();
					}
				});
			});

			// Append modal
			$("body").append(modal);

			// Focus Nick
			modal.find("input.nick").focus();
		});

		$("#headline").append(btn);
	},

	// Check if is user logged in
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

				// Check if is logged in
				login.is = data.logged_in;
				login.visitor = data.is_visitor;
				if(!login.is && !login.visitor){
					login.login_btn();
				} else {
					login.logout_btn();
				}

				// Logged in user can add post
				if(login.is){
					new_post.create();
				}

				// Initialize
				posts.init();
			}
		});
	},
}

// New post function
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
			$.post({
				dataType: "json",
				url: "ajax.php",
				data: {
					action: "insert",
					text: new_post.obj.find(".e_text").val(),
					//text: new_post.obj.find(".e_text").text(),
					feeling: new_post.obj.find(".i_feeling").val(),
					persons: new_post.obj.find(".i_persons").val(),
					location: new_post.obj.find(".i_location").val(),
					content_type: new_post.obj.find(".i_content_type").val(),
					content: new_post.obj.find(".i_content").val(),
					privacy: new_post.obj.find(".privacy").data("val")
				},
				success: function(data){
					if(data.error){
						$("body").error_msg(data.msg);
						return ;
					}

					// Empty inputs
					new_post.clear();

					// Create empty post
					var post = $('#prepared .post_row').clone();

					// Update post data and apply scripts
					post.post_fill(data);
					post.apply_post();

					// Prepend
					posts.add_new(post);
				}
			});
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

// Attach the event handler to the document
$(document).ajaxError(function(){
	$("body").error_msg("Ajax request failed.");
});

// Apply events on post editing
$.fn.apply_edit = function(data){
	// Parse link
	var ignored_links = [], is_content = false;

	return this.each(function(){
		var modal = $(this);

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
		};

		var add_content = function(type, data){
			if(!data)
				return;

			modal.find(".e_loading").hide();
			var content = modal.find(".content").empty();
			var clear = $('<button class="clear"></button>');
			clear.click(remove_content);

			if(typeof cnt_funcs[type] === "function")
				content.append(clear).append(cnt_funcs[type](data)).css("display", "block");

			modal.find(".i_content_type").val(type);
			modal.find(".i_content").val(JSON.stringify(data));
			is_content = true;
		};

		var parse_link = function(t){
			if(is_content)
				return;

			t.replace(/(https?:\/\/[^\s]+)/g, function(link, a, b) {
			//t.replace(/(https?:\/\/([^\s]+|\&nbsp\;))(:?\&nbsp\;|\s|$)/g, function(link, a, b) {
			//t.replace(/(https?\:\/\/(.*))(\&nbsp\;|\s|$)/g, function(link, a, b) {
				if(ignored_links.indexOf(link) !== -1)
					return ;

				add_content_loading();

				// Parse link
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

						// This one is ignored now
						ignored_links.push(link);

						// If is not valid
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

			$.post({
				xhr: function(){
					var xhr = new window.XMLHttpRequest();

					// Upload progress
					xhr.upload.addEventListener("progress", function(evt){
						if (evt.lengthComputable) {
							var percentComplete = evt.loaded / evt.total;
							//Do something with upload progress
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

		// Set data and key listeners for text div
		//modal.find(".e_text").text(data.plain_text)
		modal.find(".e_text").val(data.plain_text)
		/*.keydown(function(e) {
			if(e.keyCode === 13){
				document.execCommand('insertHTML', false, "\n");
				return false;
			}
		})/*.keyup(function(e){
			var t = e.currentTarget.innerHTML;
			parse_link(t);
		})*/.on('paste', function(e) {
			//e.preventDefault();

			var items = (e.clipboardData || e.originalEvent.clipboardData).items;
			for(var i in items) {
				var item = items[i];

				// Try to parse link from plain text
				if(item.type === 'text/plain'){
					item.getAsString(function(text) {
						parse_link(text);
					});
					break;
				}

				// Try to get image from clipboard
				if(item.type.indexOf('image') !== -1){
					var file = item.getAsFile();
					upload_image(file);
					break;
				}
			}

			/*
			if(document.queryCommandSupported('insertText')){
				document.execCommand('insertText', false, text);
			} else {
				document.execCommand('paste', false, text);
			}
			*/
		});

		// Autoresize textarea
		setTimeout(function(){
			autosize($(modal.find(".e_text")));
		},0);

		// Upload image button
		var file_data = modal.find(".photo_upload");
		$(file_data).change(function(){
			upload_image(file_data[0].files[0]);
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

		// Set options_content events
		modal.find(".options_content tr").each(function(){
			var oc = $(this);
			var op = modal.find(".options li."+oc.attr("class")+" a");

			// On click clear
			oc.find(".clear").click(function(){
				oc.find("input").val("");
				op.removeClass("active");
				oc.hide();
			});

			// On click icon
			op.click(function(){
				oc.toggle();
				if(oc.find("input").val() == "")
					$(op).toggleClass("active");
				oc.find("input").focus();
			});
		});

		// Set privacy button events
		modal.find(".privacy").click(function(){
			var privacy_btn = $(this);

			// Find dropdown
			o_mask = $("#prepared .privacy_settings").clone();
			$("body").append(o_mask);
			o_mask.css({
				top: $(this).offset().top - (($(o_mask).outerHeight() - $(this).outerHeight()) / 2) +  'px',
				left: $(this).offset().left - (($(o_mask).outerWidth() - $(this).outerWidth()) / 2 ) + 'px'
			});

			// Show mask and dropdown
			$("#dd_mask").show();
			o_mask.show();

			$(o_mask).find(".set").click(function(){
				privacy_btn.data("val", $(this).data("val"));
				privacy_btn.find(".cnt").html($(this).html());
				$("#dd_mask").click();
			});

		});

		// Set privacy button content
		modal.find(".privacy").data("val", data.privacy);
		modal.find(".privacy .cnt").html($("#prepared .privacy_settings .set[data-val="+data.privacy+"]").html());

		// Add content
		if(data.content_type){
			try{
				data.content = JSON.parse(data.content)
				add_content(data.content_type, data.content);
			} catch(err) {}
		}

		// Drag & Drop
		modal.find(".drop_space").filedrop(upload_image);
	});
};

// Fill post data
$.fn.post_fill = function(data){
	var post = $(this);

	post.data("id", data.id);

	if(parseInt(data.is_hidden)) {
		post.addClass("is_hidden");
	}
	post.find(".b_overlay .button").click(function(){
		var overlay = post.find(".b_overlay");
		var elementTop = $(overlay).offset().top;
		var elementBottom = elementTop + $(overlay).outerHeight();
		$(overlay).hide();

		var showOverlay = function() {
			$(overlay).css("display", ""); // .show() would cause display:block;
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

	/*
	var chars = 380;
	if(data.text.length > chars){
		var b_more = [];
		b_more.push($("<span>" + data.text.substr(0, chars) + "</span>"));
		b_more.push($("<span>&hellip;&nbsp;</span>"));
		b_more.push($('#prepared .show_more').clone());
		b_more.push($("<span>" + data.text + "</span>").hide());
		post.find(".b_text").html(b_more);

		b_more[2].click(function(){
			$(b_more).each(function(){
				$(this).toggle();
			});
		});
	}
	*/

	var height = 200;
	if(data.text.length > 400 && post.find(".show_more").length == 0){
		post.find(".b_text").css("max-height", height+"px");
		var show_more = $('#prepared .show_more').clone();
		show_more.insertAfter(post.find(".b_text"));
		show_more.click(function(){
			$(this).remove();
			post.find(".b_text").css("max-height", '');
		});
	} else if(post.find(".show_more").length != 0) {
		post.find(".show_more").remove();
	}

	// Highlight
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

// Close modal
$.fn.close = function(){
	$(this).remove();
	$("body").css("overflow", "auto");
};

// Apply events on post
$.fn.apply_post = function(){
	return this.each(function(){
		var post = $(this);
		var post_id = post.data("id");

		// If is not logged in can't edit post
		if(!login.is){
			$(post).find(".b_tools").css("display", "none").click(function(){});
			return ;
		}

		// On click tools
		$(post).find(".b_tools").css("display", "inline-block").click(function(){
			// Clone dropdown
			o_mask = $('#prepared .post_tools').clone();
			$("body").append(o_mask);
			o_mask.css({
				top: $(this).offset().top + $(this).outerHeight() + 5 + 'px',
				left: $(this).offset().left + $(this).outerWidth() - $(o_mask).outerWidth() - 5 + 'px'
			});

			// Show mask and dropdown
			$("#dd_mask").show();
			o_mask.show();

			// Edit post event
			$(o_mask).find(".edit_post").click(function(){
				// Hide mask
				$("#dd_mask").click();

				// Load data
				$.get({
					dataType: "json",
					url: "ajax.php",
					data: {action: "edit_data", id: post_id},
					success: function(data){
						if(data.error){
							$("body").error_msg(data.msg);
							return ;
						}

						// Clone modal
						var modal = $('#prepared .edit_modal').clone();
						$("body").css("overflow", "hidden");

						// Fullfill new modal with data and turn on functionality
						modal.apply_edit(data);

						// On close
						modal.find(".close").click(function(){
							modal.close();
						});

						// On save
						modal.find(".save").click(function(){
							$.post({
								dataType: "json",
								url: "ajax.php",
								data: {
									action: "update",
									id: post_id,
									text: modal.find(".e_text").val(),
									//text: modal.find(".e_text").text(),
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
										return ;
									}

									data.id = post_id;
									post.post_fill(data);
									modal.close();
								}
							});
						});

						// Append modal
						$("body").append(modal);
					}
				});
			});

			// Edit date event
			$(o_mask).find(".edit_date").click(function(){
				// Hide mask
				$("#dd_mask").click();

				// Load data
				$.get({
					dataType: "json",
					url: "ajax.php",
					data: {action: "get_date", id: post_id},
					success: function(data){
						if(data.error){
							$("body").error_msg(data.msg);
							return ;
						}

						// Clone modal
						var modal = $('#prepared .edit_date_modal').clone();
						$("body").css("overflow", "hidden");

						modal.find(".year").val(data[0]);
						modal.find(".month").val(data[1]);
						modal.find(".day").val(data[2]);
						modal.find(".hour").val(data[3]);
						modal.find(".minute").val(data[4]);

						// Initialize datepick
						datepick(modal.find(".datepicker"));

						// On close
						modal.find(".close").click(function(){
							modal.close();
						});

						// On save
						modal.find(".save").click(function(){
							$.post({
								dataType: "json",
								url: "ajax.php",
								data: {
									action: "set_date",
									id: post_id,
									date: [
										modal.find(".year").val(),
										modal.find(".month").val(),
										modal.find(".day").val(),
										modal.find(".hour").val(),
										modal.find(".minute").val()
									]
								},
								success: function(data){
									if(data.error){
										modal.find(".modal-body").error_msg(data.msg);
										return ;
									}

									post.find(".b_date").text(data.datetime);
									modal.close();
								}
							});
						});

						// Append modal
						$("body").append(modal);
					}
				});
			});

			// Hide event
			if(post.hasClass("is_hidden")) {
				$(o_mask).find(".hide").hide();
			} else {
				$(o_mask).find(".show").hide();
			}
			$(o_mask).find(".hide, .show").click(function(){
				// Hide mask
				$("#dd_mask").click();

				var action = 'hide';
				if(post.hasClass("is_hidden")) {
					action = 'show';
				}

				$.post({
					dataType: "json",
					url: "ajax.php",
					data: {
						action: action,
						id: post_id
					},
					success: function(data){
						if(data.error){
							$("body").error_msg(data.msg);
							return ;
						}

						if(action == 'hide') {
							post.addClass("is_hidden");
						} else {
							post.removeClass("is_hidden");
						}
					}
				});
			});

			// Delete event
			$(o_mask).find(".delete_post").click(function(){
				// Hide mask
				$("#dd_mask").click();

				// Clone modal
				var modal = $('#prepared .delete_modal').clone();
				$("body").css("overflow", "hidden");

				// On close
				modal.find(".close").click(function(){
					modal.close();
				});

				// On delete
				modal.find(".delete").click(function(){
					$.post({
						dataType: "json",
						url: "ajax.php",
						data: {
							action: "delete",
							id: post_id
						},
						success: function(data){
							if(data.error){
								modal.find(".modal-body").error_msg(data.msg);
								return ;
							}

							post.remove();
							modal.close();
						}
					});
				});

				// Append modal
				$("body").append(modal);
			});
		});
	});
};

// File drop
$.fn.filedrop = function(callback){
	return this.each(function() {
		// Stop default browser actions
		$(this).bind('dragover dragleave drop', function(event) {
			event.stopPropagation();
			event.preventDefault();
		});

		// Check if is element being dragged
		var dropTimer;
		$(this).on('dragover', function(e) {
			var dt = e.originalEvent.dataTransfer;
			if(dt.types != null && (dt.types.indexOf ? dt.types.indexOf('Files') != -1 : dt.types.contains('application/x-moz-file'))){
				$(".e_drop").css("display", "flex");
				window.clearTimeout(dropTimer);
			}
		}).on('dragleave drop', function(e) {
			dropTimer = window.setTimeout(function() {
				$(".e_drop").hide();
			}, 25);
		});

		// Catch drop event
		$(this).bind('drop', function(event) {
			// Get all files that are dropped
			var files = event.originalEvent.target.files || event.originalEvent.dataTransfer.files

			// Pass first uploaded file to callback
			if(typeof callback === "function" && files.length > 0)
				callback(files[0]);

			return false
		})
	})
};

// Start application
login.init();

// Check if is element being dragged
var dragTimer;
$(document).on('dragover', function(e) {
	var dt = e.originalEvent.dataTransfer;
	if(dt.types != null && (dt.types.indexOf ? dt.types.indexOf('Files') != -1 : dt.types.contains('application/x-moz-file'))){
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