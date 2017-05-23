(function () {

	var csv = d3.csvParse(d3.select("#csv").text());
	
	var data = [];
	[...new Set(csv.map(d => d.Category))].forEach(d => {
	  data.push({
		Category: d,
		data: csv.filter(e => e.Category === d).map(f =>
		  ({
			gene: f.Name,
			time: 1
		  }))
	  });
	})
	
	//console.log(data)

	// Reading JSON Files
    var data = {
            "data": data
    };
	
    // svg 
    var width = 1000;
    var height = 1000;
    var margin = {
        top: 0,
        right: 0,
        bottom: 0,
        left: 0
    };
    var radius = Math.min(width - margin.right - margin.left, height - margin.top - margin.bottom);

    var color = d3.scale.category20b();

    var partition = d3.layout.partition()
    
    .children(function (d, depth) {
        return d.data !== void(0) ? d.data : null;
    })
    
    .value(function (d) {
        return d.time;
    })
        .size([2 * Math.PI, radius / 3]);

    var arc = d3.svg.arc()
        .startAngle(function (d) {
        return d.x;
    })
        .endAngle(function (d) {
        return d.x + d.dx - 0.015 / d.depth;
    })
        .innerRadius(function (d) {
        return d.y;
    })
        .outerRadius(function (d) {
        return d.y + d.dy - 1;
    });

    var svg = d3.selectAll("#edit-area")
        .append("svg")
        .attr("width", width)
        .attr("height", height)
        .append("g")
        .attr("transform", "translate(" + (width - margin.left - margin.right) / 2 + "," + (height - margin.top - margin.bottom) / 2 + ")");

    var arcs = svg.selectAll("g.arc")
        .data(partition.nodes(data).slice(1))
        .enter()
        .append("g")
        .attr("class", "arc");

    arcs.append("path")
        .attr("d", function (d) {
        return arc(d);
    })
        .style("fill", function (d, i) {
        return color(i);
    });

    arcs.on("mouseover", function (d) {

        svg.append("text")
            .attr("id", "tooltip")
            .attr("x", arc.centroid(d)[0])
            .attr("y", arc.centroid(d)[1])
            .attr("text-anchor", "middle")
            .attr("fill", "black")
            .text(function () {
            if (d.depth === 1) {
				return "[Category] " + d.Category;
            } else {
				return d.gene;
            }
        });

    })
        .on("mouseout", function () {
        d3.select("#tooltip").remove();
    });

}).call(this);