function init_page() {

	$(".navbar-nav li[data-cat='dashboard']").addClass("active").find("a").removeClass("text-danger");

	$("table.table").on("click", ".save_date", function () {

		firestore.collection("jobs").doc($(this).val()).update({
				installation_date: moment($(this).parent().parent().find(".datepicker").data("date")).toDate()
			})
			.then(function () {
				console.log("Job updated");
				window.location.reload();
			})
			.catch(function (error) {
				console.error("Error writing document: ", error);
			});
	});

	firestore.collection("jobs").get().then(function (jobs) {
		var calendar_dates = [];
		jobs.forEach(function (j) {
			var j = j.data();
			if ((!userData.branch.length || (j.branch_id && userData.branch.includes(j.branch_id.id))) && !j.done && !j.deleted) {
				if (!j.installation_date && !j.installation_status) {
					$("#jobs_pending").append('<tr><td class="align-middle"><a href="job.html#' + j.id + '">' + j.id + '</a></td><td class="align-middle">' + list_links(j.flexitanks, "flexitank") + '</td><td class="align-middle"><div class="datepicker"></div><div class="text-center top-10"><button class="btn btn-success btn-sm save_date d-block" value="' + j.id + '">Save date</button></div></td></tr>');
				} else {

					var start = moment.unix(j.installation_date.seconds);
					calendar_dates.push({
						title: j.id + " (" + (j.flexitanks ? j.flexitanks.length : 0) + ")" + team_names(j.teams),
						color: (moment().diff(start) > 0 ? 'red' : 'green'),
						textColor: 'white',
						start: start.format("YYYY-MM-DD"),
						url: 'job.html#' + j.id
					});

				}
			}
		});

		if (!$("#jobs_pending .save_date").length) {
			$("#jobs_pending").append('<tr><td class="text-center" colspan="3">All jobs have been already scheduled.</td></tr>');
		}

		var calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
			header: {
				left: 'prev,next today',
				center: 'title',
				right: 'dayGridWeek,dayGridMonth'
			},
			contentHeight: "auto",
			height: "auto",
			droppable: true,
			plugins: ['dayGrid', 'bootstrap', 'interaction'],
			defaultView: 'dayGridWeek',
			views: {
				dayGridWeek: {
					levelLimit: 0,
					eventLimit: 15
				},
				dayGridMonth: {
					eventLimit: 5
				}
			},
			validRange: {
    start: moment().subtract(1, "months").toDate()
,
				end: moment().add(2, "months").toDate()
  },
			navLinks: true, // can click day/week names to navigate views
			editable: true,
			eventLimit: true, // allow "more" link when too many events
			events: calendar_dates,
			eventDrop: function (data) {

				if (!confirm("Are you sure you would like to move " + data.event.title + " to " + moment(data.event.start).format("DD MMM YYYY") + "?")) {
					revertFunc();
				} else {
					var job_id = data.event.url.split("#");

					firestore.collection("jobs").doc(job_id[1]).update({
							installation_date: moment(data.event.start).toDate()
						}).then(function () {
							console.log("Job updated");
						})
						.catch(function (error) {
							console.error("Error writing document: ", error);
						});
				}

			}

		});
		calendar.render();

		$('.datepicker').datetimepicker({
			inline: true,
			icons: {
				time: 'fa fa-clock'
			},
			useCurrent: false,
			minDate: moment().subtract(1, "days"),
			maxDate: moment().add(3, "months"),
			stepping: 10,
			format: 'L'
		});

		var flexitanks = [];
		var firebaseFlexitanks = firestore.collection("flexitanks").where("installation_status", "==", "completed").get().then(function (data) {
			data.forEach(function (d) {
				if (!userData.branch.length || (d.data().branch_id && userData.branch.includes(d.data().branch_id.id))) {
					flexitanks.push(d.data());
				}
			});
			$('#flexitanks').DataTable({
				stateSave: true,
				data: flexitanks,
				responsive: true,
				autoWidth: false,
				columns: [
					{
						data: "id",
						title: "Flexitank",
						createdCell: function (td, cellData, rowData, row, col) {
							$(td).html('<a href="flexitank.html#' + cellData + '"><strong>' + cellData + '</strong></a>');
						}
				},
					{
						data: "installation_record.container_number",
						title: "Container"
				},
					{
						data: "customer_name",
						title: "Customer name"
				}
        ]
			});
		});
		$(".loading").addClass("d-none");


		firestore.collection("flexitanks").where("installation_status", "==", "completed").where("t_updated", "<", moment().subtract(1, "month").valueOf()).get().then(function (flexitanks) {

			flexitanks.forEach(function (f) {
				firestore.collection("flexitanks").doc(f.id).update({
					installation_status: "completed_timeout",
					qa_timeout: moment().valueOf()
				});
			});
		});
		/*
			firestore.collection("jobs").get().then(function (jobs) {

			jobs.forEach(function (f) {
				var j = f.data();
				if (!j.deleted) {
				firestore.collection("jobs").doc(f.id).update({
					deleted: 0
				});
				}
			});
		});
		*/
	});
}

function team_names(teams) {
	if (teams && teams.length) {
		var results = [];
		teams.forEach(function (t) {
			results.push(t.id);
		});
		return '\n' + results.join(", ");
	}
}

function list_links(array, page, connector, classes) {
	var response = '';
	array.forEach(function (f) {
		response += '<a href="' + page + '.html#' + f.id + '" class="' + classes + '">' + f.id + '</a>' + (connector ? connector : '<br />');
	});
	return response;
}
