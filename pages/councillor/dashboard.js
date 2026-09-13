// load google charts
google.charts.load('current', {'packages':['corechart']});
google.charts.setOnLoadCallback(drawCharts);

// ticket information
var tickets = [
    ['Water Supply', 'Open'],
    ['Road Damage', 'Open'],
    ['Electrical Faults', 'Resolved'],
    ['Water Supply', 'In Progress'],
    ['Sanitation', 'Open'],
    ['Fire', 'Resolved'],
    ['Animal Control', 'Open'],
    ['Road Damage', 'In Progress'],
    ['Electrical Faults', 'Open'],
    ['Animal Control', 'Resolved']
];

// update ticket summary
function updateTicketSummary() {
    var total = tickets.length;
    var open = 0;
    var progress = 0;
    var resolved = 0;

    for (var i = 0; i < tickets.length; i++) {
        if (tickets[i][1] == 'Open') {
            open++;
        } else if (tickets[i][1] == 'In Progress') {
            progress++;
        } else if (tickets[i][1] == 'Resolved') {
            resolved++;
        }
    }

    document.getElementById('totalTickets').textContent = total;
    document.getElementById('openTickets').textContent = open;
    document.getElementById('progressTickets').textContent = progress;
    document.getElementById('resolvedTickets').textContent = resolved;
}

// draw charts
function drawCharts() {
    updateTicketSummary();
    drawCategoryChart();
    drawStatusChart();
}

// draw category chart
function drawCategoryChart() {
    var categoryCounts = {};

    for (var i = 0; i < tickets.length; i++) {
        var category = tickets[i][0];

        if (categoryCounts[category]) {
            categoryCounts[category]++;
        } else {
            categoryCounts[category] = 1;
        }
    }

    var dataArray = [
        ['category', 'tickets']
    ];

    for (var category in categoryCounts) {
        dataArray.push([
            category,
            categoryCounts[category]
        ]);
    }

    var data = google.visualization.arrayToDataTable(dataArray);

    var options = {
        title: 'Number of Tickets by Category',
        pieHole: 0.4,
        legend: {
            position: 'right'
        },
        chartArea: {
            width: '85%',
            height: '75%'
        },
        colors: [
            '#172A39',
            '#9AC7BF',
            '#E9E4E0',
            '#6B7C85',
            '#A8B8B5',
            '#4F6868'
        ]
    };

    var chart = new google.visualization.PieChart(
        document.getElementById('categoryChart')
    );

    chart.draw(data, options);
}

// draw status chart
function drawStatusChart() {
    var open = 0;
    var progress = 0;
    var resolved = 0;

    for (var i = 0; i < tickets.length; i++) {
        if (tickets[i][1] == 'Open') {
            open++;
        } else if (tickets[i][1] == 'In Progress') {
            progress++;
        } else if (tickets[i][1] == 'Resolved') {
            resolved++;
        }
    }

    var data = google.visualization.arrayToDataTable([
        ['status', 'tickets'],
        ['Open', open],
        ['In Progress', progress],
        ['Resolved', resolved]
    ]);

    var options = {
        title: 'Number of Tickets by Status',
        legend: {
            position: 'none'
        },
        bar: {
            groupWidth: '65%'
        },
        chartArea: {
            width: '75%',
            height: '70%'
        },
        colors: [
            '#E9E4E0'
        ],
        vAxis: {
            minValue: 0,
            format: '0'
        }
    };

    var chart = new google.visualization.ColumnChart(
        document.getElementById('statusChart')
    );

    chart.draw(data, options);
}