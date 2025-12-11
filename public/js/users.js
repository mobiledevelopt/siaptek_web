(() => {
    "use strict"
    const tooltipInit = () => {
		const t = [].slice.call(
			document.querySelectorAll('[data-bs-toggle="tooltip"]')
		);
		const l = t.map((tooltipTriggerEl) => {
			return new bootstrap.Tooltip(tooltipTriggerEl, {
				trigger: "hover",
			});
		});
	};

    const _confirm = (() => {
        const t = [].slice.call(document.querySelectorAll(".btn-delete"))
        const l = t.map((e) => {
			const a = e.getAttribute("href");
			e.addEventListener("click", (o) => {
				o.preventDefault();
				Swal.fire({
					title: "Are you sure?",
					text: "You won't be able to revert this!",
					icon: "warning",
					showCancelButton: true,
				}).then((r) => {
					if (r.isConfirmed) {
						window.location.href = a;
					}
				});
			});
		})
    })

    const _dtInit = (() => {
        if (window.jQuery) {
            const $ = window.jQuery
            const _dt = $('.datatable')

            const customDt = (e) => {
                e.find(".pagination").addClass("pagination")
                _confirm()
                tooltipInit()
            };
            _dt.length &&
                _dt.each((k, v) => {
                    const $this = $(v)
                    const opt = $.extend(
                        {
                            dom:
                                "<'row mx-0'<'col-md-6'l><'col-md-6'f>>" +
                                "<'table-responsive scrollbar'tr>" +
                                "<'row g-0 align-items-center justify-content-center justify-content-sm-between'<'col-auto mb-2 mb-sm-0 px-3'i><'col-auto px-3'p>>",
                            columnDefs: [
                                { targets: "no-sort", orderable: false },
                                { targets: "text-center", className: "text-center" },
                                { targets: "text-end", className: "text-end" },
                                { targets: "pe-2", className: "pe-2" },
                            ],
                            buttons: [
                                {"extend": "excelHtml5",
     "exportOptions": {
         "modifier" : {
            "order" : "index", 
            "page" : "all", 
            "search" : "applied"
         }
     }},
	{"extend": "pdfHtml5",
     "exportOptions": {
         "modifier" : {
            "order" : "index", 
            "page" : "all", 
            "search" : "applied"
         }
     }},
	{"extend": "csvHtml5",
     "exportOptions": {
         "modifier" : {
            "order" : "index", 
            "page" : "all", 
            "search" : "applied"
         }
     }},
     ]
                        },
                        $this.data("options")
                    );
                    $this.DataTable(opt)
                    const $wrapper = $this.closest(".dataTables_wrapper")
                    customDt($wrapper)
                    $this.on("draw.dt", () => {
                        return customDt($wrapper)
                    })
                })
        }
    })()
})()