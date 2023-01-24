var datepick = function(container) {
	var datepick = {
		months: ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"],

		tbody: null,

		m: null,
		daysInMonth: null,
		inc_m: function() {
			if(this.m == 11) {
				this.inc_y();
				this.m = 0;
			} else {
				this.m++;
			}

			this.daysInMonth = (new Date(this.y, this.m+1, 0)).getDate();
		},

		dec_m: function() {
			if(this.m == 0) {
				this.dec_y();
				this.m = 11;
			} else {
				this.m--;
			}

			this.daysInMonth = (new Date(this.y, this.m+1, 0)).getDate();
		},

		y: null,
		inc_y: function() {
			this.y++;
		},

		dec_y: function() {
			this.y--;
		},

		set_date: function(m, y) {
			this.m = m;
			this.y = y;

			this.daysInMonth = (new Date(this.y, this.m+1, 0)).getDate();
		},

		build_table: function(container) {
			var table = $("<table>");

			// Thead
			var thead = $(
				'<thead>' +
					'<tr>' +
						'<th><button type="button" class="button blue prev_y" title="Previous Year">&lt;&lt;</button></th>' +
						'<th><button type="button" class="button blue prev" title="Previous Month">&lt;</button></th>' +
						'<th class="month-pick" colspan="3" title="Select Month">'+this.months[this.m]+' '+this.y+'</th>' +
						'<th><button type="button" class="button blue next" title="Next Month">&gt;</button></th>' +
						'<th><button type="button" class="button blue next_y" title="Next Year">&gt;&gt;</button></th>' +
					'</tr>' +
					'<tr>' +
						'<th>Mo</th>' +
						'<th>Tu</th>' +
						'<th>We</th>' +
						'<th>Th</th>' +
						'<th>Fr</th>' +
						'<th>Sa</th>' +
						'<th>Su</th>' +
					'</tr>' +
				'</thead>'
			);

			var x = this;
			$(thead).find(".prev_y").click(function(){
				x.dec_y();
				x.load_table();
				$(thead).find(".month-pick").text(x.months[x.m]+' '+x.y);
			});
			$(thead).find(".prev").click(function(){
				x.dec_m();
				x.load_table();
				$(thead).find(".month-pick").text(x.months[x.m]+' '+x.y);
			});

			$(thead).find(".next_y").click(function(){
				x.inc_y();
				x.load_table();
				$(thead).find(".month-pick").text(x.months[x.m]+' '+x.y);
			});
			$(thead).find(".next").click(function(){
				x.inc_m();
				x.load_table();
				$(thead).find(".month-pick").text(x.months[x.m]+' '+x.y);
			});

			$(thead).find(".month-pick").click(function(){

			});

			$(table).append(thead);

			// Tbody
			this.tbody = $("<tbody>");
			$(table).append(this.tbody);

			$(container).append(table);
		},

		load_table: function() {
			$(this.tbody).empty();

			// Get first day of week
			var dayOfWeek = new Date(this.y, this.m, 1).getDay();

			// 0 is sunday - last day
			if(dayOfWeek == 0) {
				dayOfWeek = 7;
			}

			// Previous month
			this.dec_m();

			var daysInPrevMonth = this.daysInMonth - dayOfWeek + 2;
			for (var i = dayOfWeek, j = daysInPrevMonth; i > 1; i--, j++) {
				this.append_date(j, false);
			}

			// Current month
			this.inc_m();
			for (var i = 1; i <= this.daysInMonth; i++) {
				this.append_date(i, true);
			}

			// Next month
			this.inc_m();
			i = 1;
			while (this.i % 7 != 0) {
				this.append_date(i++, false);
			}

			this.dec_m();
			this.i = 0;
		},

		tr: null,
		i: 0,
		append_date: function (d, active) {
			var y = this.y;
			var m = this.m;

			if(this.i % 7 == 0 || this.i == 0) {
				this.tr = $("<tr>");
				$(this.tbody).append(this.tr);
			}

			var td = $("<td>");
			td.text(d);

			if(active) {
				td.addClass("active");
			}

			if(this.today[0] == d && this.today[1] == m && this.today[2] == y) {
				td.addClass("today");
			}

			var selected = this.selected;
			if(selected[0].val() == d && selected[1].val() == m + 1 && selected[2].val() == y) {
				td.addClass("selected");
				selected[3] = td;
			}

			$(td).click(function(){
				console.log("Set date: " + y + "/" + (m + 1) + "/" + d);

				selected[0].val(d);
				selected[1].val(m + 1);
				selected[2].val(y);

				$(selected[3]).removeClass("selected");
				selected[3] = this;
				$(selected[3]).addClass("selected");
			});

			$(this.tr).append(td);
			this.i++;
		},

		init: function (container) {
			var today = new Date();
			this.today = [today.getDate(), today.getMonth(), today.getFullYear()];

			this.selected = [
				$(container).find(".day"),
				$(container).find(".month"),
				$(container).find(".year")
			];

			var months = $(container).find(".month_names").val();
			this.months = months.split(",");

			this.set_date(this.selected[1].val() - 1, this.selected[2].val());

			this.build_table(container);
			this.load_table();
		}
	};

	datepick.init(container);
	return datepick;
};