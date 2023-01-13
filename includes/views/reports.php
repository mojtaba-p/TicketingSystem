<div class="bootstrap-wrapper">
    <div class="container">

        <div class="row mt-2">
            <div class="col-12">

               <form  method="get">
                   <input type="hidden" name="post_type" value="ts_ticket">
                   <input type="hidden" name="page" value="reports.php">
                  <?php do_action ("ts_restrict_reports") ?>
               </form>
            </div>
        </div>
        <div class="row">

            <div class="col-12">
                <div class="my-3 p-3 bg-white rounded shadow-sm">
                    <button onclick="printCanvas( 'tickets-count' )" class="btn btn-primary float-left">چاپ</button>
                    <h6 class="border-bottom border-gray pb-2 mb-0">تعداد تیکت ها در هر روز</h6>
                    <canvas id="tickets-count" height="100"></canvas>
                </div>
            </div>


            <div class="col-4">
                <div class="my-3 p-3 bg-white rounded shadow-sm">
                    <button onclick="printCanvas( 'tickets-type' )" class="btn btn-primary float-left">چاپ</button>
                    <h6 class="border-bottom border-gray pb-2 mb-0">فراوانی دسته بندی تیکت ها</h6>
                    <canvas id="tickets-type" height="200"></canvas>
                </div>
            </div>

            <div class="col-8">
                <div class="my-3 p-3 bg-white rounded shadow-sm">
                    <button onclick="printCanvas( 'tickets-type-per-day' )" class="btn btn-primary float-left">چاپ</button>
                    <h6 class="border-bottom border-gray pb-2 mb-0"> فراوانی دسته بندی تیکت ها در هر روز</h6>
                    <canvas id="tickets-type-per-day" height="100"></canvas>

                </div>
            </div>

        </div>
    </div>
</div>

<script>
    let rndColor = function () {
        var letters = '0123456789ABCDEF';
        var color = '#';
        for (var i = 0; i < 6; i++) {
            color += letters[Math.floor(Math.random() * 16)];
        }
        return color;
    };

    $ = jQuery;
    let count_el = $("#tickets-count");
    let types_el = $("#tickets-type");
    let types_per_day_el = $("#tickets-type-per-day");

    let options = {
        scales: {
            yAxes: [{
                ticks: {
                    beginAtZero: true
                }
            }]
        }
    };
    // main chart
    let count_chart_data = {
        labels: [<?php foreach ( $this_month_tickets as $c => $v ): echo "'$c',"; endforeach; ?>],
        datasets: [{
            label: 'تعداد تیکت',
            data: [<?php foreach ( $this_month_tickets as $c => $v ): echo $v . ","; endforeach; ?>],
            borderWidth: 3,
            backgroundColor: 'lightblue'
        }],
    };

    let count_chart_options = {
        type: 'line',
        data: count_chart_data,
        options: options
    };
    let count_chart = new Chart(count_el, count_chart_options);

    //second chart
    let ticket_type_chart_data = {
        labels: [<?php foreach ( $tickets_by_type as $c => $v ): echo "'$c',"; endforeach; ?>],
        datasets: [{
            label: 'دسته بندی تیکت ها',
            data: [<?php foreach ( $tickets_by_type as $c => $v ): echo $v . ","; endforeach; ?>],
            borderWidth: 3,
            backgroundColor: 'lightred'
        }],
    };

    let type_count_chart_options = {
        type: 'bar',
        data: ticket_type_chart_data,
        options: options
    };
    let ticket_type_chart = new Chart(types_el, type_count_chart_options);

    // third
    let perdaydata = {
        labels: [<?php foreach ( $tickets_by_type_per_day_days as $days ) {
            echo "'$days" . "',";
        } ?>],
        datasets: [
            <?php foreach ( $ticket_terms as $term ) : if ( ! in_array ( $term->term_id, $tickets_by_type_per_day_keys ) ) { continue;  } ?>
                {
                    label: '<?php echo "$term->name" ?>',
                    data: [<?php foreach ( $tickets_by_type_per_day[0][ $term->term_id ] as $s => $value): ?> <?php echo ( $value[1] ) ?>, <?php endforeach; ?> ],
                    borderColor: rndColor(),
                    borderWidth: 1,
                },
            <?php endforeach; ?>
        ]
    };

    var types_perday_chart = new Chart(types_per_day_el, {
        type: 'line',
        data: perdaydata,
   });

    function printCanvas( id )
    {
        var dataUrl = document.getElementById(id).toDataURL(); //attempt to save base64 string to server using this var
        var windowContent = '<!DOCTYPE html>';
        windowContent += '<html>'
        windowContent += '<head><title>Print canvas</title></head>';
        windowContent += '<body>'
        windowContent += '<img src="' + dataUrl + '">';
        windowContent += '</body>';
        windowContent += '</html>';
        var printWin = window.open('','','width=340,height=260');
        printWin.document.open();
        printWin.document.write(windowContent);
        printWin.document.close();
        printWin.focus();
        printWin.print();
        printWin.close();
    }
</script>
