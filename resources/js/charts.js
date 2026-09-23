/**
 * Charting libraries, kept out of the main bundle.
 *
 * ApexCharts and Chart.js together weigh more than three hundred kilobytes
 * gzipped, and only the two dashboards draw charts. Loading them from the
 * shared entry made every checkout iframe download and parse them for
 * nothing. Pages that need charts pull this entry in themselves.
 */
import ApexCharts from 'apexcharts';
import Chart from 'chart.js/auto';

window.ApexCharts = ApexCharts;
window.Chart = Chart;
