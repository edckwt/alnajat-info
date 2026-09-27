//BarChart//
/*
new Chart(document.getElementById("bar-chart-sample"), {
  type: 'bar',
  data: {
    labels: ["2013", "2014", "2015", "2016", "2017", "2018"],
   datasets: [{
   label: "دقائق الاستماع",
   backgroundColor: "rgba(0,163,136,1)",
   borderColor: "rgba(0,163,136,1)",
   pointBackgroundColor: "rgba(0,163,136,1)",
   pointBorderColor: "#fff",
   pointHighlightFill: "#fff",
   pointHighlightStroke: "rgba(0,163,136,1))",
   data: [2205503, 3848804, 4000000, 5000000, 6000000, 440757]
  },{
   label: "المستمعين",
   backgroundColor: "rgba(204,68,82,1)",
   borderColor: "rgba(204,68,82,1)",
   pointBackgroundColor: "rgba(204,68,82,1)",
   pointBorderColor: "#fff",
   pointHighlightFill: "#fff",
   pointHighlightStroke: "rgba(204,68,82,1)",
   data: [698859, 1561027, 2000000, 3000000, 4000000, 252324]
  }
   ]
  }
});
*/
//BarChart Age//
/*
new Chart(document.getElementById("bar-chart-age"), {
  type: 'bar',
  data: {
    labels: ["Al-Luhaidan", "Àbdulbaset", "English", "Malayalam", "Alafasey", "Albanian","Alsha'rawy","Alsa'dy","Russian","Spanish"],
   datasets: [{
   label: "الزيارات",
   backgroundColor:"#79bd8f",
   borderColor:"#79bd8f",
   pointBackgroundColor:"#79bd8f",
   pointBorderColor: "#fff",
   pointHighlightFill: "#fff",
   pointHighlightStroke: "#79bd8f",
   data: [50000, 45000, 40000, 35000, 34000, 30000, 29000, 27000, 26000, 22000]
  }]
  }
});
*/
//BarChart top 10//
/*
new Chart(document.getElementById("bar-chart-top10"), {
  type: 'bar',
  data: {
    labels: ["Saudi Arabia", "Egypt", "United States", "India", "Iraq", "United Kingdom","Turkey","Canada","Morocco","Jordan"],
   datasets: [{
   label: "الزيارات",
   backgroundColor:["#31152b", "#47d9bf", "#bf2441", "#83d3df", "#f2d03b","#2595ff", "#723147", "#007391", "#cc4452", "#f15f22"],
   borderColor:["#31152b", "#47d9bf", "#bf2441", "#83d3df", "#f2d03b","#2595ff", "#723147", "#007391", "#cc4452", "#f15f22"],
   pointBackgroundColor:["#31152b", "#47d9bf", "#bf2441", "#83d3df", "#f2d03b","#2595ff", "#723147", "#007391", "#cc4452", "#f15f22"],
   pointBorderColor: "#fff",
   pointHighlightFill: "#fff",
   pointHighlightStroke: ["#31152b", "#47d9bf", "#bf2441", "#83d3df", "#f2d03b","#2595ff", "#723147", "#007391", "#cc4452", "#f15f22"],
   data: [50000, 45000, 40000, 35000, 34000, 30000, 29000, 27000, 26000, 22000]
  }]
  }
});
*/
/*
new Chart(document.getElementById("pie-chart3"), {
  type: 'pie',
  data:  {
      labels: ["الجوال", "الأجهزة اللوحية", "أجهزة سطح المكتب"],
      datasets: [{
        label: "الأجهزة المستخدمة",
        backgroundColor: ["#00a388", "#003056", "#ff6138"],
        data: [950000,89000,700000],
        borderWidth:[0]
      }]
    },
  options: {
      legend: {
      display: false,
      position: 'bottom',
    },
    pieceLabel: {
      // render 'label', 'value', 'percentage', 'image' or custom function, default is 'percentage'
      render: 'percentage',

      // precision for percentage, default is 0
      precision: 0,

      // identifies whether or not labels of value 0 are displayed, default is false
      showZero: true,

      // font size, default is defaultFontSize
      fontSize: 14,

      // font color, can be color array for each data or function for dynamic color, default is defaultFontColor
      fontColor: '#fff',

      // font style, default is defaultFontStyle
      fontStyle: 'normal',

      // font family, default is defaultFontFamily
      fontFamily: "Helvetica Neue', 'Helvetica', 'Arial', sans-serif",

      // draw label in arc, default is false
      arc: false,

      // position to draw label, available value is 'default', 'border' and 'outside'
      // default is 'default'
      position: 'border',

      // draw label even it's overlap, default is false
      overlap: true,

      // show the real calculated percentages from the values and don't apply the additional logic to fit the percentages to 100 in total, default is false
      showActualPercentages: true,
    }
  }
});
*/
/*
new Chart(document.getElementById("pie-chart4"), {
  type: 'pie',
  data:  {
      labels: ["Radio Android", "Radio IOS","learning Quran IOS"],
      datasets: [{
        label: "النوع",
        backgroundColor: ["#6b930a", "#fc4349", "#2f343b"],
        data: [15000,22000,1800],
        borderWidth:[0]
      }]
    },
  options: {
      legend: {
      display: false,
      position: 'bottom',
    },
    pieceLabel: {
      // render 'label', 'value', 'percentage', 'image' or custom function, default is 'percentage'
      render: 'percentage',

      // precision for percentage, default is 0
      precision: 0,

      // identifies whether or not labels of value 0 are displayed, default is false
      showZero: true,

      // font size, default is defaultFontSize
      fontSize: 14,

      // font color, can be color array for each data or function for dynamic color, default is defaultFontColor
      fontColor: '#fff',

      // font style, default is defaultFontStyle
      fontStyle: 'normal',

      // font family, default is defaultFontFamily
      fontFamily: "Helvetica Neue', 'Helvetica', 'Arial', sans-serif",

      // draw label in arc, default is false
      arc: false,

      // position to draw label, available value is 'default', 'border' and 'outside'
      // default is 'default'
      position: 'border',

      // draw label even it's overlap, default is false
      overlap: true,

      // show the real calculated percentages from the values and don't apply the additional logic to fit the percentages to 100 in total, default is false
      showActualPercentages: true,
    }
  }
});
*/
///Animate NUmbers
/*
$('.animate-count').each(function () {
    $(this).prop('Counter',0).animate({
        Counter: $(this).text()
    }, {
        duration: 4000,
        easing: 'swing',
        step: function (now) {
            $(this).text(Math.ceil(now));
        }
    });
});
*/
/*
function startCounter(){
	$('.animate-count').each(function (index) {
        var size = $(this).text().split(".")[1] ? $(this).text().split(".")[1].length : 0;
	    $(this).prop('Counter',0).animate({
	        Counter: $(this).text()
	    }, {
	        duration: 4000,
	        easing: 'swing',
	        step: function (now) {
	            $(this).text(parseFloat(now).toFixed(size));
	        }
	    });
	});
}
*/