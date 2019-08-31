<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class VamBaseController extends Controller
{


	private $ROWH;
	private $CPH1;

	public function VEG($x,$y){
		$VEG[0][10]=2.08;	$VEG[0][20]=3.02;	$VEG[0][30]=4.15;	$VEG[0][40]=5.83;	$VEG[0][50]=8.09;	$VEG[0][60]=12.05;	$VEG[0][70]=17.79;	$VEG[0][80]=24.44;	$VEG[0][90]=33.74;
		$VEG[5][10]=1.79;	$VEG[5][20]=2.54;	$VEG[5][30]=3.48;	$VEG[5][40]=4.82;	$VEG[5][50]=6.63;	$VEG[5][60]=9.66;	$VEG[5][70]=14.09;	$VEG[5][80]=19.20;	$VEG[5][90]=25.84;
		$VEG[10][10]=1.56;	$VEG[10][20]=2.18;	$VEG[10][30]=2.95;	$VEG[10][40]=4.04;	$VEG[10][50]=5.50;	$VEG[10][60]=7.85;	$VEG[10][70]=11.31;	$VEG[10][80]=15.29;	$VEG[10][90]=20.18;
		$VEG[15][10]=1.37;	$VEG[15][20]=1.89;	$VEG[15][30]=2.53;	$VEG[15][40]=3.44;	$VEG[15][50]=4.63;	$VEG[15][60]=6.46;	$VEG[15][70]=9.18;	$VEG[15][80]=12.33;	$VEG[15][90]=16.04;
		$VEG[20][10]=1.21;	$VEG[20][20]=1.65;	$VEG[20][30]=2.20;	$VEG[20][40]=2.96;	$VEG[20][50]=3.94;	$VEG[20][60]=5.38;	$VEG[20][70]=7.53;	$VEG[20][80]=10.05;	$VEG[20][90]=12.95;
		$VEG[25][10]=1.08;	$VEG[25][20]=1.46;	$VEG[25][30]=1.92;	$VEG[25][40]=2.57;	$VEG[25][50]=3.39;	$VEG[25][60]=4.52;	$VEG[25][70]=6.24;	$VEG[25][80]=8.29;	$VEG[25][90]=10.59;
		$VEG[30][10]=0.97;	$VEG[30][20]=1.30;	$VEG[30][30]=1.69;	$VEG[30][40]=2.26;	$VEG[30][50]=2.94;	$VEG[30][60]=3.84;	$VEG[30][70]=5.23;	$VEG[30][80]=6.90;	$VEG[30][90]=8.77;
		$VEG[35][10]=0.88;	$VEG[35][20]=1.17;	$VEG[35][30]=1.50;	$VEG[35][40]=1.99;	$VEG[35][50]=2.56;	$VEG[35][60]=3.29;	$VEG[35][70]=4.42;	$VEG[35][80]=5.79;	$VEG[35][90]=7.34;
		$VEG[40][10]=0.80;	$VEG[40][20]=1.06;	$VEG[40][30]=1.34;	$VEG[40][40]=1.77;	$VEG[40][50]=2.26;	$VEG[40][60]=2.84;	$VEG[40][70]=3.76;	$VEG[40][80]=4.91;	$VEG[40][90]=6.21;
		$VEG[45][10]=0.73;	$VEG[45][20]= 0.96;	$VEG[45][30]= 1.21;	$VEG[45][40]= 1.59;	$VEG[45][50]=2.00;	$VEG[45][60]=2.47;	$VEG[45][70]=3.23;	$VEG[45][80]=4.19;	$VEG[45][90]=5.30;
		$VEG[50][10]=0.67;	$VEG[50][20]=0.88;	$VEG[50][30]=1.09;	$VEG[50][40]=1.43;	$VEG[50][50]=1.78;	$VEG[50][60]=2.16;	$VEG[50][70]=2.80;	$VEG[50][80]=3.61;	$VEG[50][90]=4.56;
		$VEG[55][10]=0.62;	$VEG[55][20]=0.81;	$VEG[55][30]=0.99;	$VEG[55][40]=1.29;	$VEG[55][50]=1.59;	$VEG[55][60]=1.91;	$VEG[55][70]=2.43;	$VEG[55][80]=3.12;	$VEG[55][90]=3.95;
		$VEG[60][10]=0.57;	$VEG[60][20]=0.74;	$VEG[60][30]= 0.90;	$VEG[60][40]= 1.17;	$VEG[60][50]= 1.43;	$VEG[60][60]=1.69;	$VEG[60][70]=2.13;	$VEG[60][80]=2.72;	$VEG[60][90]=3.45;
		$VEG[65][10]=0.53;	$VEG[65][20]=0.69;	$VEG[65][30]=0.83;	$VEG[65][40]=1.06;	$VEG[65][50]=1.29;	$VEG[65][60]=1.51;	$VEG[65][70]=1.88;	$VEG[65][80]=2.39;	$VEG[65][90]=3.03;
		$VEG[70][10]=0.50;	$VEG[70][20]=0.64;	$VEG[70][30]=0.76;	$VEG[70][40]=0.97;	$VEG[70][50]=1.17;	$VEG[70][60]=1.35;	$VEG[70][70]=1.67;	$VEG[70][80]=2.11;	$VEG[70][90]=2.67;
		$VEG[75][10]=0.47;	$VEG[75][20]=0.59;	$VEG[75][30]=0.70;	$VEG[75][40]=0.89;	$VEG[75][50]=1.07;	$VEG[75][60]=1.22;	$VEG[75][70]=1.49;	$VEG[75][80]=1.87;	$VEG[75][90]=2.37;
		$VEG[80][10]=0.44;	$VEG[80][20]=0.55;	$VEG[80][30]=0.65;	$VEG[80][40]=0.82;	$VEG[80][50]=0.98;	$VEG[80][60]=1.10;	$VEG[80][70]=1.33;	$VEG[80][80]=1.66;	$VEG[80][90]=2.12;
		$VEG[85][10]=0.41;	$VEG[85][20]=0.52;	$VEG[85][30]=0.60;	$VEG[85][40]=0.76;	$VEG[85][50]=0.89;	$VEG[85][60]=1.00;	$VEG[85][70]=1.20;	$VEG[85][80]=1.49;	$VEG[85][90]=1.90;
		$VEG[90][10]=0.39;	$VEG[90][20]=0.49;	$VEG[90][30]=0.56;	$VEG[90][40]=0.70;	$VEG[90][50]=0.82;	$VEG[90][60]=0.92;	$VEG[90][70]=1.09;	$VEG[90][80]=1.24;	$VEG[90][90]=1.71;
		$VEG[95][10]=0.37;	$VEG[95][20]=0.46;	$VEG[95][30]=0.52;	$VEG[95][40]=0.65;	$VEG[95][50]=0.76;	$VEG[95][60]=0.84;	$VEG[95][70]=0.99;	$VEG[95][80]=1.21;	$VEG[95][90]=1.54;
		$VEG[100][10]=0.35;	$VEG[100][20]=0.43;	$VEG[100][30]=0.49;	$VEG[100][40]=0.60;	$VEG[100][50]=0.70;	$VEG[100][60]=0.77;	$VEG[100][70]=0.90;	$VEG[100][80]=1.10;	$VEG[100][90]=1.40;
		$VEG[105][10]=0.33;	$VEG[105][20]=0.40;	$VEG[105][30]=0.46;	$VEG[105][40]=0.56;	$VEG[105][50]=0.65;	$VEG[105][60]=0.71;	$VEG[105][70]=0.82;	$VEG[105][80]=1.00;	$VEG[105][90]=1.27;
		$VEG[110][10]=0.32;	$VEG[110][20]=0.38;	$VEG[110][30]=0.43;	$VEG[110][40]=0.53;	$VEG[110][50]=0.60;	$VEG[110][60]=0.66;	$VEG[110][70]=0.76;	$VEG[110][80]=0.91;	$VEG[110][90]=1.16;
		$VEG[115][10]=0.30;	$VEG[115][20]=0.36;	$VEG[115][30]=0.41;	$VEG[115][40]=0.49;	$VEG[115][50]=0.56;	$VEG[115][60]=0.61;	$VEG[115][70]=0.70;	$VEG[115][80]=0.83;	$VEG[115][90]=1.07;
		$VEG[120][10]=0.29;	$VEG[120][20]=0.34;	$VEG[120][30]=0.38;	$VEG[120][40]=0.46;	$VEG[120][50]=0.53;	$VEG[120][60]=0.57;	$VEG[120][70]=0.64;	$VEG[120][80]=0.77;	$VEG[120][90]=0.98;
		$VEG[125][10]=0.28;	$VEG[125][20]=0.33;	$VEG[125][30]=0.36;	$VEG[125][40]=0.43;	$VEG[125][50]=0.49;	$VEG[125][60]=0.53;	$VEG[125][70]=0.60;	$VEG[125][80]=0.71;	$VEG[125][90]=0.90;


		return $VEG[$x][$y];

	}

	public function ETH($x,$y){
		$ETH[0][10]=	0.511;	$ETH[0][20]=	0.468;	$ETH[0][30]=	0.429;	$ETH[0][40]=	0.395;	$ETH[0][50]=	0.364;	$ETH[0][60]=	0.336;	$ETH[0][70]=	0.312;	$ETH[0][80]=	0.290;	$ETH[0][90]=	0.271;
		$ETH[5][10]=	0.520;	$ETH[5][20]=	0.476;	$ETH[5][30]=	0.436;	$ETH[5][40]=	0.400;	$ETH[5][50]=	0.368;	$ETH[5][60]=	0.340;	$ETH[5][70]=	0.314;	$ETH[5][80]=	0.292;	$ETH[5][90]=	0.272;
		$ETH[10][10]=0.528;	$ETH[10][20]=0.483;	$ETH[10][30]=0.442;	$ETH[10][40]=0.405;	$ETH[10][50]=0.373;	$ETH[10][60]=0.346;	$ETH[10][70]=0.170;	$ETH[10][80]=0.294;	$ETH[10][90]=0.274;
		$ETH[15][10]=0.537;	$ETH[15][20]=0.490;	$ETH[15][30]=0.448;	$ETH[15][40]=0.410;	$ETH[15][50]=0.377;	$ETH[15][60]=0.346;	$ETH[15][70]=0.320;	$ETH[15][80]=0.296;	$ETH[15][90]=0.275;
		$ETH[20][10]=0.545;	$ETH[20][20]=0.497;	$ETH[20][30]=0.453;	$ETH[20][40]=0.415;	$ETH[20][50]=0.380;	$ETH[20][60]=0.349;	$ETH[20][70]=0.322;	$ETH[20][80]=0.298;	$ETH[20][90]=0.276;
		$ETH[25][10]=0.552;	$ETH[25][20]=0.503;	$ETH[25][30]=0.459;	$ETH[25][40]=0.419;	$ETH[25][50]=0.384;	$ETH[25][60]=0.352;	$ETH[25][70]=0.324;	$ETH[25][80]=0.299;	$ETH[25][90]=0.278;
		$ETH[30][10]=0.559;	$ETH[30][20]=0.509;	$ETH[30][30]=0.464;	$ETH[30][40]=0.424;	$ETH[30][50]=0.387;	$ETH[30][60]=0.355;	$ETH[30][70]=0.327;	$ETH[30][80]=0.301;	$ETH[30][90]=0.279;
		$ETH[35][10]=0.566;	$ETH[35][20]=0.515;	$ETH[35][30]=0.469;	$ETH[35][40]=0.428;	$ETH[35][50]=0.391;	$ETH[35][60]=0.358;	$ETH[35][70]=0.329;	$ETH[35][80]=0.303;	$ETH[35][90]=0.280;
		$ETH[40][10]=0.572;	$ETH[40][20]=0.520;	$ETH[40][30]=0.473;	$ETH[40][40]=0.431;	$ETH[40][50]=0.394;	$ETH[40][60]=0.360;	$ETH[40][70]=0.331;	$ETH[40][80]=0.304;	$ETH[40][90]=0.281;
		$ETH[45][10]=0.577;	$ETH[45][20]=0.525;	$ETH[45][30]=0.477;	$ETH[45][40]=0.435;	$ETH[45][50]=0.397;	$ETH[45][60]=0.363;	$ETH[45][70]=0.332;	$ETH[45][80]=0.306;	$ETH[45][90]=0.282;
		$ETH[50][10]=0.583;	$ETH[50][20]=0.529;	$ETH[50][30]=0.481;	$ETH[50][40]=0.438;	$ETH[50][50]=0.399;	$ETH[50][60]=0.365;	$ETH[50][70]=0.334;	$ETH[50][80]=0.307;	$ETH[50][90]=0.283;
		$ETH[55][10]=0.588;	$ETH[55][20]=0.534;	$ETH[55][30]=0.485;	$ETH[55][40]=0.441;	$ETH[55][50]=0.402;	$ETH[55][60]=0.367;	$ETH[55][70]=0.336;	$ETH[55][80]=0.308;	$ETH[55][90]=0.284;
		$ETH[60][10]=0.592;	$ETH[60][20]=0.538;	$ETH[60][30]=0.488;	$ETH[60][40]=0.444;	$ETH[60][50]=0.404;	$ETH[60][60]=0.369;	$ETH[60][70]=0.337;	$ETH[60][80]=0.310;	$ETH[60][90]=0.285;
		$ETH[65][10]=0.596;	$ETH[65][20]=0.541;	$ETH[65][30]=0.491;	$ETH[65][40]=0.446;	$ETH[65][50]=0.406;	$ETH[65][60]=0.371;	$ETH[65][70]=0.339;	$ETH[65][80]=0.311;	$ETH[65][90]=0.286;
		$ETH[70][10]=0.600;	$ETH[70][20]=0.544;	$ETH[70][30]=0.494;	$ETH[70][40]=0.449;	$ETH[70][50]=0.408;	$ETH[70][60]=0.372;	$ETH[70][70]=0.340;	$ETH[70][80]=0.312;	$ETH[70][90]=0.287;
		$ETH[75][10]=0.603;	$ETH[75][20]=0.547;	$ETH[75][30]=0.496;	$ETH[75][40]=0.451;	$ETH[75][50]=0.410;	$ETH[75][60]=0.374;	$ETH[75][70]=0.341;	$ETH[75][80]=0.313;	$ETH[75][90]=0.288;
		$ETH[80][10]=0.606;	$ETH[80][20]=0.549;	$ETH[80][30]=0.498;	$ETH[80][40]=0.452;	$ETH[80][50]=0.411;	$ETH[80][60]=0.375;	$ETH[80][70]=0.342;	$ETH[80][80]=0.314;	$ETH[80][90]=0.288;
		$ETH[85][10]=0.608;	$ETH[85][20]=0.551;	$ETH[85][30]=0.500;	$ETH[85][40]=0.454;	$ETH[85][50]=0.413;	$ETH[85][60]=0.376;	$ETH[85][70]=0.343;	$ETH[85][80]=0.314;	$ETH[85][90]=0.289;
		$ETH[90][10]=0.610;	$ETH[90][20]=0.553;	$ETH[90][30]=0.501;	$ETH[90][40]=0.455;	$ETH[90][50]=0.414;	$ETH[90][60]=0.377;	$ETH[90][70]=0.344;	$ETH[90][80]=0.315;	$ETH[90][90]=0.290;
		$ETH[95][10]=0.612;	$ETH[95][20]=0.555;	$ETH[95][30]=0.503;	$ETH[95][40]=0.456;	$ETH[95][50]=0.415;	$ETH[95][60]=0.378;	$ETH[95][70]=0.345;	$ETH[95][80]=0.316;	$ETH[95][90]=0.290;
		$ETH[100][10]=0.613;	$ETH[100][20]=0.556;	$ETH[100][30]=0.504;	$ETH[100][40]=0.457;	$ETH[100][50]=0.416;	$ETH[100][60]=0.379;	$ETH[100][70]=0.346;	$ETH[100][80]=0.316;	$ETH[100][90]=0.291;
		$ETH[105][10]=0.614;	$ETH[105][20]=0.556;	$ETH[105][30]=0.504;	$ETH[105][40]=0.458;	$ETH[105][50]=0.416;	$ETH[105][60]=0.379;	$ETH[105][70]=0.346;	$ETH[105][80]=0.317;	$ETH[105][90]=0.291;
		$ETH[110][10]=0.614;	$ETH[110][20]=0.557;	$ETH[110][30]=0.505;	$ETH[110][40]=0.458;	$ETH[110][50]=0.417;	$ETH[110][60]=0.380;	$ETH[110][70]=0.347;	$ETH[110][80]=0.317;	$ETH[110][90]=0.292;
		$ETH[115][10]=0.614;	$ETH[115][20]=0.557;	$ETH[115][30]=0.505;	$ETH[115][40]=0.458;	$ETH[115][50]=0.417;	$ETH[115][60]=0.380;	$ETH[115][70]=0.347;	$ETH[115][80]=0.318;	$ETH[115][90]=0.292;
		$ETH[120][10]=0.613;	$ETH[120][20]=0.556;	$ETH[120][30]=0.504;	$ETH[120][40]=0.458;	$ETH[120][50]=0.417;	$ETH[120][60]=0.380;	$ETH[120][70]=0.347;	$ETH[120][80]=0.318;	$ETH[120][90]=0.293;
		$ETH[125][10]=0.612;	$ETH[125][20]=0.555;	$ETH[125][30]=0.504;	$ETH[125][40]=0.458;	$ETH[125][50]=0.417;	$ETH[125][60]=0.380;	$ETH[125][70]=0.347;	$ETH[125][80]=0.318;	$ETH[125][90]=0.293;
				
				

		return $ETH[$x][$y];
	}

	public function PTH($x,$y){
		$PTH[0][10]=	0.510;	$PTH[0][20]=	0.464;	$PTH[0][30]=	0.423;	$PTH[0][40]=	0.385;	$PTH[0][50]=	0.349;	$PTH[0][60]=	0.317;	$PTH[0][70]=	0.286;	$PTH[0][80]=	0.259;	$PTH[0][90]=	0.234;
		$PTH[5][10]=	0.518;	$PTH[5][20]=	0.472;	$PTH[5][30]=	0.429;	$PTH[5][40]=	0.389;	$PTH[5][50]=	0.353;	$PTH[5][60]=	0.319;	$PTH[5][70]=	0.288;	$PTH[5][80]=	0.260;	$PTH[5][90]=	0.234;
		$PTH[10][10]=0.527;	$PTH[10][20]=0.479;	$PTH[10][30]=0.434;	$PTH[10][40]=0.394;	$PTH[10][50]=0.356;	$PTH[10][60]=0.321;	$PTH[10][70]=0.289;	$PTH[10][80]=0.260;	$PTH[10][90]=0.233;
		$PTH[15][10]=0.535;	$PTH[15][20]=0.485;	$PTH[15][30]=0.44;	$PTH[15][40]=0.398;	$PTH[15][50]=0.359;	$PTH[15][60]=0.323;	$PTH[15][70]=0.290;	$PTH[15][80]=0.260;	$PTH[15][90]=0.233;
		$PTH[20][10]=0.543;	$PTH[20][20]=0.492;	$PTH[20][30]=0.445;	$PTH[20][40]=0.402;	$PTH[20][50]=0.362;	$PTH[20][60]=0.325;	$PTH[20][70]=0.290;	$PTH[20][80]=0.261;	$PTH[20][90]=0.232;
		$PTH[25][10]=0.550;	$PTH[25][20]=0.498;	$PTH[25][30]=0.449;	$PTH[25][40]=0.406;	$PTH[25][50]=0.365;	$PTH[25][60]=0.327;	$PTH[25][70]=0.292;	$PTH[25][80]=0.261;	$PTH[25][90]=0.231;
		$PTH[30][10]=0.557;	$PTH[30][20]=0.503;	$PTH[30][30]=0.454;	$PTH[30][40]=0.409;	$PTH[30][50]=0.367;	$PTH[30][60]=0.329;	$PTH[30][70]=0.293;	$PTH[30][80]=0.261;	$PTH[30][90]=0.231;
		$PTH[35][10]=0.563;	$PTH[35][20]=0.508;	$PTH[35][30]=0.458;	$PTH[35][40]=0.412;	$PTH[35][50]=0.37;	$PTH[35][60]=0.330;	$PTH[35][70]=0.293;	$PTH[35][80]=0.261;	$PTH[35][90]=0.230;
		$PTH[40][10]=0.569;	$PTH[40][20]=0.513;	$PTH[40][30]=0.462;	$PTH[40][40]=0.415;	$PTH[40][50]=0.372;	$PTH[40][60]=0.331;	$PTH[40][70]=0.294;	$PTH[40][80]=0.261;	$PTH[40][90]=0.229;
		$PTH[45][10]=0.575;	$PTH[45][20]=0.518;	$PTH[45][30]=0.466;	$PTH[45][40]=0.418;	$PTH[45][50]=0.374;	$PTH[45][60]=0.333;	$PTH[45][70]=0.294;	$PTH[45][80]=0.260;	$PTH[45][90]=0.229;
		$PTH[50][10]=0.580;	$PTH[50][20]=0.522;	$PTH[50][30]=0.469;	$PTH[50][40]=0.42;	$PTH[50][50]=0.375;	$PTH[50][60]=0.334;	$PTH[50][70]=0.295;	$PTH[50][80]=0.260;	$PTH[50][90]=0.228;
		$PTH[55][10]=0.585;	$PTH[55][20]=0.526;	$PTH[55][30]=0.472;	$PTH[55][40]=0.423;	$PTH[55][50]=0.377;	$PTH[55][60]=0.335;	$PTH[55][70]=0.295;	$PTH[55][80]=0.260;	$PTH[55][90]=0.227;
		$PTH[60][10]=0.589;	$PTH[60][20]=0.529;	$PTH[60][30]=0.475;	$PTH[60][40]=0.425;	$PTH[60][50]=0.378;	$PTH[60][60]=0.335;	$PTH[60][70]=0.295;	$PTH[60][80]=0.260;	$PTH[60][90]=0.227;
		$PTH[65][10]=0.593;	$PTH[65][20]=0.532;	$PTH[65][30]=0.477;	$PTH[65][40]=0.426;	$PTH[65][50]=0.378;	$PTH[65][60]=0.336;	$PTH[65][70]=0.295;	$PTH[65][80]=0.259;	$PTH[65][90]=0.226;
		$PTH[70][10]=0.596;	$PTH[70][20]=0.535;	$PTH[70][30]=0.479;	$PTH[70][40]=0.428;	$PTH[70][50]=0.38;	$PTH[70][60]=0.336;	$PTH[70][70]=0.295;	$PTH[70][80]=0.259;	$PTH[70][90]=0.225;
		$PTH[75][10]=0.599;	$PTH[75][20]=0.538;	$PTH[75][30]=0.481;	$PTH[75][40]=0.429;	$PTH[75][50]=0.381;	$PTH[75][60]=0.337;	$PTH[75][70]=0.295;	$PTH[75][80]=0.258;	$PTH[75][90]=0.224;
		$PTH[80][10]=0.602;	$PTH[80][20]=0.54;	$PTH[80][30]=0.482;	$PTH[80][40]=0.43;	$PTH[80][50]=0.382;	$PTH[80][60]=0.337;	$PTH[80][70]=0.295;	$PTH[80][80]=0.258;	$PTH[80][90]=0.223;
		$PTH[85][10]=0.604;	$PTH[85][20]=0.541;	$PTH[85][30]=0.484;	$PTH[85][40]=0.431;	$PTH[85][50]=0.382;	$PTH[85][60]=0.337;	$PTH[85][70]=0.295;	$PTH[85][80]=0.257;	$PTH[85][90]=0.222;
		$PTH[90][10]=0.606;	$PTH[90][20]=0.543;	$PTH[90][30]=0.484;	$PTH[90][40]=0.431;	$PTH[90][50]=0.382;	$PTH[90][60]=0.337;	$PTH[90][70]=0.294;	$PTH[90][80]=0.256;	$PTH[90][90]=0.221;
		$PTH[95][10]=0.607;	$PTH[95][20]=0.544;	$PTH[95][30]=0.485;	$PTH[95][40]=0.432;	$PTH[95][50]=0.382;	$PTH[95][60]=0.336;	$PTH[95][70]=0.294;	$PTH[95][80]=0.256;	$PTH[95][90]=0.220;
		$PTH[100][10]=0.608;	$PTH[100][20]=0.544;	$PTH[100][30]=0.485;	$PTH[100][40]=0.432;	$PTH[100][50]=0.382;	$PTH[100][60]=0.336;	$PTH[100][70]=0.293;	$PTH[100][80]=0.255;	$PTH[100][90]=0.219;
		$PTH[105][10]=0.609;	$PTH[105][20]=0.544;	$PTH[105][30]=0.485;	$PTH[105][40]=0.432;	$PTH[105][50]=0.382;	$PTH[105][60]=0.335;	$PTH[105][70]=0.292;	$PTH[105][80]=0.254;	$PTH[105][90]=0.218;
		$PTH[110][10]=0.609;	$PTH[110][20]=0.544;	$PTH[110][30]=0.485;	$PTH[110][40]=0.431;	$PTH[110][50]=0.381;	$PTH[110][60]=0.335;	$PTH[110][70]=0.292;	$PTH[110][80]=0.253;	$PTH[110][90]=0.217;
		$PTH[115][10]=0.608;	$PTH[115][20]=0.544;	$PTH[115][30]=0.485;	$PTH[115][40]=0.43;	$PTH[115][50]=0.38;	$PTH[115][60]=0.334;	$PTH[115][70]=0.291;	$PTH[115][80]=0.252;	$PTH[115][90]=0.216;
		$PTH[120][10]=0.608;	$PTH[120][20]=0.543;	$PTH[120][30]=0.484;	$PTH[120][40]=0.429;	$PTH[120][50]=0.379;	$PTH[120][60]=0.333;	$PTH[120][70]=0.290;	$PTH[120][80]=0.251;	$PTH[120][90]=0.215;
		$PTH[125][10]=0.606;	$PTH[125][20]=0.542;	$PTH[125][30]=0.482;	$PTH[125][40]=0.428;	$PTH[125][50]=0.378;	$PTH[125][60]=0.332;	$PTH[125][70]=0.288;	$PTH[125][80]=0.250;	$PTH[125][90]=0.214;		
				

		return $PTH[$x][$y];
	}

	public function PEG($x,$y){
		$PEG[0][10]=	2.68;	$PEG[0][20]=	4.05;	$PEG[0][30]=	7.08;	$PEG[0][40]=	12.37;	$PEG[0][50]=	18.4;	$PEG[0][60]=	31.32;	$PEG[0][70]=	45.74;	$PEG[0][80]=	74.45;	$PEG[0][90]=	122.03;
		$PEG[5][10]=	2.23;	$PEG[5][20]=	3.34;	$PEG[5][30]=	5.61;	$PEG[5][40]=	9.35;	$PEG[5][50]=	13.85;	$PEG[5][60]=	22.87;	$PEG[5][70]=	33.04;	$PEG[5][80]=	52.63;	$PEG[5][90]=	85.15;
		$PEG[10][10]=1.89;	$PEG[10][20]=2.79;	$PEG[10][30]=4.52;	$PEG[10][40]=7.22;	$PEG[10][50]=10.65;	$PEG[10][60]=17.05;	$PEG[10][70]=24.41;	$PEG[10][80]=37.99;	$PEG[10][90]=60.93;
		$PEG[15][10]=1.63;	$PEG[15][20]=2.36;	$PEG[15][30]=3.69;	$PEG[15][40]=5.69;	$PEG[15][50]=8.34;	$PEG[15][60]=12.96;	$PEG[15][70]=18.41;	$PEG[15][80]=28.00;	$PEG[15][90]=44.62;
		$PEG[20][10]=1.42;	$PEG[20][20]=2.02;	$PEG[20][30]=3.06;	$PEG[20][40]=4.57;	$PEG[20][50]=6.65;	$PEG[20][60]=10.04;	$PEG[20][70]=14.15;	$PEG[20][80]=21.04;	$PEG[20][90]=33.38;
		$PEG[25][10]=1.25;	$PEG[25][20]=1.74;	$PEG[25][30]=2.57;	$PEG[25][40]=3.73;	$PEG[25][50]=5.39;	$PEG[25][60]=7.91;	$PEG[25][70]=11.08;	$PEG[25][80]=16.10;	$PEG[25][90]=25.45;
		$PEG[30][10]=1.11;	$PEG[30][20]=1.52;	$PEG[30][30]=2.18;	$PEG[30][40]=3.09;	$PEG[30][50]=4.43;	$PEG[30][60]=6.34;	$PEG[30][70]=8.81;	$PEG[30][80]=12.55;	$PEG[30][90]=19.76;
		$PEG[35][10]=0.99;	$PEG[35][20]=1.34;	$PEG[35][30]=1.88;	$PEG[35][40]=2.6;    $PEG[35][50]=3.69;	$PEG[35][60]=5.15;	$PEG[35][70]=7.12;	$PEG[35][80]=9.94;	$PEG[35][90]=15.60;
		$PEG[40][10]=0.89;	$PEG[40][20]=1.18;	$PEG[40][30]=1.63;	$PEG[40][40]=2.21;	$PEG[40][50]=3.11;	$PEG[40][60]=4.25;	$PEG[40][70]=5.84;	$PEG[40][80]=7.99;	$PEG[40][90]=12.49;
		$PEG[45][10]=0.81;	$PEG[45][20]=1.06;	$PEG[45][30]=1.43;	$PEG[45][40]=1.91;	$PEG[45][50]=2.65;	$PEG[45][60]=3.55;	$PEG[45][70]=4.85;	$PEG[45][80]=6.52;	$PEG[45][90]=10.15;
		$PEG[50][10]=0.73;	$PEG[50][20]=0.95;	$PEG[50][30]=1.26;	$PEG[50][40]=1.66;	$PEG[50][50]=2.29;	$PEG[50][60]=3.00;	$PEG[50][70]=4.08;	$PEG[50][80]=5.39;	$PEG[50][90]=8.35;
		$PEG[55][10]=0.67;	$PEG[55][20]=0.86;	$PEG[55][30]=1.13;	$PEG[55][40]=1.47;	$PEG[55][50]=1.99;	$PEG[55][60]=2.57;	$PEG[55][70]=3.46;	$PEG[55][80]=4.51;	$PEG[55][90]=6.95;
		$PEG[60][10]=0.62;	$PEG[60][20]=0.78;	$PEG[60][30]=1.01;	$PEG[60][40]=1.3;	$PEG[60][50]=1.75;	$PEG[60][60]=2.22;	$PEG[60][70]=2.98;	$PEG[60][80]=3.82;	$PEG[60][90]=5.85;
		$PEG[65][10]=0.57;	$PEG[65][20]=0.71;	$PEG[65][30]=0.91;	$PEG[65][40]=1.17;	$PEG[65][50]=1.55;	$PEG[65][60]=1.93;	$PEG[65][70]=2.58;	$PEG[65][80]=3.28;	$PEG[65][90]=4.97;
		$PEG[70][10]=0.53;	$PEG[70][20]=0.66;	$PEG[70][30]=0.83;	$PEG[70][40]=1.06;	$PEG[70][50]=1.38;	$PEG[70][60]=1.70;	$PEG[70][70]=2.26;	$PEG[70][80]=2.83;	$PEG[70][90]=4.26;
		$PEG[75][10]=0.49;	$PEG[75][20]=0.6;    $PEG[75][30]=0.76;	$PEG[75][40]=0.96;	$PEG[75][50]=1.24;	$PEG[75][60]=1.51;	$PEG[75][70]=1.99;	$PEG[75][80]=2.47;	$PEG[75][90]=3.69;
		$PEG[80][10]=0.46;	$PEG[80][20]=0.56;	$PEG[80][30]=0.7;    $PEG[80][40]=0.88;	$PEG[80][50]=1.12;	$PEG[80][60]=1.35;	$PEG[80][70]=1.77;	$PEG[80][80]=2.18;	$PEG[80][90]=3.22;
		$PEG[85][10]=0.43;	$PEG[85][20]=0.52;	$PEG[85][30]=0.65;	$PEG[85][40]=0.81;	$PEG[85][50]=1.02;	$PEG[85][60]=1.22;	$PEG[85][70]=1.59;	$PEG[85][80]=1.94;	$PEG[85][90]=2.83;
		$PEG[90][10]=0.4;    $PEG[90][20]=0.49;	$PEG[90][30]=0.61;	$PEG[90][40]=0.75;	$PEG[90][50]=0.93;	$PEG[90][60]=1.10;	$PEG[90][70]=1.43;	$PEG[90][80]=1.73;	$PEG[90][90]=2.50;
		$PEG[95][10]=0.38;	$PEG[95][20]=0.45;	$PEG[95][30]=0.57;	$PEG[95][40]=0.7;	$PEG[95][50]=0.86;	$PEG[95][60]=1.01;	$PEG[95][70]=1.30;	$PEG[95][80]=1.56;	$PEG[95][90]=2.23;
		$PEG[100][10]=0.35;	$PEG[100][20]=0.43;	$PEG[100][30]=0.53;	$PEG[100][40]=0.66;	$PEG[100][50]=0.79;	$PEG[100][60]=0.92;	$PEG[100][70]=1.18;	$PEG[100][80]=1.42;	$PEG[100][90]=2.00;
		$PEG[105][10]=0.33;	$PEG[105][20]=0.4;	$PEG[105][30]=0.5;	$PEG[105][40]=0.62;	$PEG[105][50]=0.74;	$PEG[105][60]=0.85;	$PEG[105][70]=1.08;	$PEG[105][80]=1.29;	$PEG[105][90]=1.80;
		$PEG[110][10]=0.32;	$PEG[110][20]=0.38;	$PEG[110][30]=0.47;	$PEG[110][40]=0.59;	$PEG[110][50]=0.69;	$PEG[110][60]=0.79;	$PEG[110][70]=1.00;	$PEG[110][80]=1.19;	$PEG[110][90]=1.63;
		$PEG[115][10]=0.3;	$PEG[115][20]=0.36;	$PEG[115][30]=0.45;	$PEG[115][40]=0.56;	$PEG[115][50]=0.64;	$PEG[115][60]=0.74;	$PEG[115][70]=0.30;	$PEG[115][80]=1.09;	$PEG[115][90]=1.48;
		$PEG[120][10]=0.28;	$PEG[120][20]=0.34;	$PEG[120][30]=0.43;	$PEG[120][40]=0.53;	$PEG[120][50]=0.6;	$PEG[120][60]=0.69;	$PEG[120][70]=0.86;	$PEG[120][80]=1.02;	$PEG[120][90]=1.35;
		$PEG[125][10]=0.27;	$PEG[125][20]=0.32;	$PEG[125][30]=0.41;	$PEG[125][40]=0.51;	$PEG[125][50]=0.57;	$PEG[125][60]=0.65;	$PEG[125][70]=0.80;	$PEG[125][80]=0.95;	$PEG[125][90]=1.24;


		return $PEG[$x][$y];
	}


    public function EG_VISCOSITY($EGT, $EGX){
    	$REM= fmod($EGX, 10);
		$EGX1=$EGX-$REM;
		$EGRX1=$this->EG_VISCOSITY1($EGT,$EGX1);
		$EGX2=$EGX1+10;
		$EGRX2=$this->EG_VISCOSITY1($EGT,$EGX2);
		$Y0 = $EGRX1;
		$Y1 = $EGRX2;
		$YY11 = ($EGX - $EGX2) / ($EGX1 - $EGX2) * $Y0;
		$YY22 = ($EGX - $EGX1) / ($EGX2 - $EGX1) * $Y1;
		$YY2 = $YY11 + $YY22;
		return $YY2;
    }


	public function EG_VISCOSITY1($TVS,$X)
	{

		$REM1=fmod($TVS,5);
		$EGT1=(int) ($TVS - $REM1);
		$NX= (int) $X;
		$EGST1 = 0.0;
		$EGST2 = 0.0;
		if($NX==0)
		{
			$EGST1=(exp(-6.325 - 0.033974 * $EGT1 + 0.0002829 * $EGT1 * $EGT1 - 0.0000018309 *pow($EGT1,3) + 0.0000000055184 * pow($EGT1,4)))*1000;
		}
		else
		{
			$EGST1=$this->VEG($EGT1,$NX);
		}
		$EGT2=$EGT1+5;
		if($NX==0)
		{
			$EGST2=(exp(-6.325 - 0.033974 * $EGT2 + 0.0002829 * $EGT2 * $EGT2 - 0.0000018309 *pow($EGT2,3) + 0.0000000055184 * pow($EGT2,4)))*1000;
		}
		else
		{
			$EGST2=$this->VEG($EGT2,$NX);
		}
		$NY0 = $EGST1;
		$NY1 = $EGST2;
		$NYY11 = ($TVS - $EGT2) / ($EGT1 - $EGT2) * $NY0;
		$NYY22 = ($TVS - $EGT1) / ($EGT2 - $EGT1) * $NY1;
		$NYY2 = $NYY11 + $NYY22;
		return $NYY2;
	}

	public function EG_THERMAL_CONDUCTIVITY($EGT,$EGX)
	{
		$REM= fmod($EGX, 10);
		$EGX1=$EGX-$REM;
		$EGRX1=$this->EG_THERMAL_CONDUCTIVITY1($EGT,$EGX1);
		$EGX2=$EGX1+10;
		$EGRX2=$this->EG_THERMAL_CONDUCTIVITY1($EGT,$EGX2);
		$Y0 = $EGRX1;
		$Y1 = $EGRX2;
		$YY11 = ($EGX - $EGX2) / ($EGX1 - $EGX2) * $Y0;
		$YY22 = ($EGX - $EGX1) / ($EGX2 - $EGX1) * $Y1;
		$YY2 = $YY11 + $YY22;
		return $YY2;
	}

	public function EG_THERMAL_CONDUCTIVITY1($TVS,$X)
	{	

		$REM1=fmod($TVS,5);
		$EGT1=(int) ($TVS-$REM1);
		$NX=(int)$X;
		$EGST1 = 0.0; 
		$EGST2 = 0.0;
		if($NX==0)
		{
			$EGST1=0.5628 + 0.00197 * $EGT1 - 0.000008298 * $EGT1 *$EGT1;
		}
		else
		{
			$EGST1=$this->ETH($EGT1,$NX);
		}
		$EGT2=$EGT1+5;
		if($NX==0)
		{
			$EGST2=0.5628 + 0.00197 * $EGT2 - 0.000008298 * $EGT2 *$EGT2;
		}
		else
		{
			$EGST2=$this->ETH($EGT2,$NX);
		}
		$NY0 = $EGST1;
		$NY1 = $EGST2;
		$NYY11 = ($TVS - $EGT2) / ($EGT1 - $EGT2) * $NY0;
		$NYY22 = ($TVS - $EGT1) / ($EGT2 - $EGT1) * $NY1;
		$NYY2 = $NYY11 + $NYY22;
		return $NYY2;
	}

	public function EG_ROW($EGT,$EGX)
	{
		$REM = fmod($EGX, 10);
		$EGX1 = $EGX-$REM;
		$EGRT1 = $this->EG_ROW1($EGT, (double)$EGX1);
		$EGX2 = $EGX1+10;

		$EGRT2 = $this->EG_ROW1($EGT,(double)$EGX2); 
		$Y0 = $EGRT1;
		$Y1 = $EGRT2;
		$YY11 = ($EGX - $EGX2) / ($EGX1 - $EGX2) * $Y0;
		$YY22 = ($EGX - $EGX1) / ($EGX2 - $EGX1) * $Y1;
		$YY2 = $YY11 + $YY22;

		return $YY2;
	}

	public function EG_ROW1($T,$X)
	{
		$EGS = 0.0;
		$EGR = 0.0;

		if($X==0)
		{
			$EGS=$this->HOT_WATER($T);
			$EGR=$this->ROWH;
		}
		if($X==10)
		{
			if($T<95)
			{
				$EGR=-0.0000000000004* pow($T,6) + 0.0000000001* pow($T,5) - 0.00000002* pow($T,4) + 0.0000009* pow($T,3) - 0.0025* pow($T,2) - 0.2207* $T + 1018.73;
			}
			else
			{
				$EGR=-0.7559* $T + 1047.9;
			}
		}
		if($X==20)
		{
			if($T<45)
			{
				$EGR= 0.00000000002* pow($T,6) - 0.00000001* pow($T,5) + 0.000001* pow($T,4) - 0.0001* pow($T,3) + 0.0012* pow($T,2) - 0.2944* $T + 1035.67;
			}
			else
			{
				if($T<85)
				{
					$EGR= -0.000000000005* pow($T,6) + 0.000000002* pow($T,5) - 0.0000006* pow($T,4) + 0.00007* pow($T,3) - 0.0066* pow($T,2) - 0.1083* $T + 1033.8;
				}
				else
				{
					$EGR=-0.7593* $T + 1062.1;
				}
			}
		}
		if($X==30)
		{
			if($T<75)
			{
				$EGR=0.000000000007* pow($T,6) - 0.000000003* pow($T,5) + 0.0000004* pow($T,4) - 0.00002* pow($T,3) - 0.0019* pow($T,2) - 0.2822* $T + 1051.78;
			}
			else
			{
				$EGR= -0.7641* $T + 1075.5;
			}
		}
		if($X==40)
		{
			if($T<80)
			{
				$EGR= -0.0000000000008* pow($T,6) + 0.0000000003* pow($T,5) - 0.00000005* pow($T,4) + 0.000003* pow($T,3) - 0.0025* pow($T,2) - 0.306* $T + 1066.8;
			}
			else
			{
				$EGR= -0.8059* $T + 1091.8;
			}
		}
		if($X==50)
		{
			$EGR=-0.00000000000005* pow($T,6) + 0.00000000002* pow($T,5) - 0.000000004* pow($T,4) + 0.0000004* pow($T,3) - 0.0025* pow($T,2) - 0.3377* $T + 1081.08;
		}
		if($X==60)
		{
			if($T<85)
			{
				$EGR=-4 * pow(10,(-13)) * pow($T,6) + pow(10,(-10)) * pow($T,5) - 2 * pow(10,(-8)) * pow($T,4) + pow(10,(-6)) * pow($T,3) - 0.0025* pow($T,2) - 0.3696* $T + 1094.64;
			}
			else
			{
				$EGR=-0.8807* $T + 1121.1;
			}
		}
		if($X==70)
		{
			$EGR=6 * pow(10,( - 14)) * pow($T,6) - 2 * pow(10,( - 12)) * pow($T,5) - 3 * pow(10,( - 9)) * pow($T,4) + 5 * pow(10,( - 7)) * pow($T,3) - 0.0025* pow($T,2) - 0.4021* $T + 1107.5;
		}
		if($X==80)
		{
			$EGR= - 9 * pow(10,( - 15)) * pow($T,6) + 2 * pow(10,( - 11)) * pow($T,5) - 6 * pow(10,( - 9)) * pow($T,4) + 7 * pow(10,( - 7)) * pow($T,3) - 0.0025* pow($T,2) - 0.4355* $T + 1119.82;
		}
		if($X==90)
		{
			$EGR= - 6 * pow(10,( - 13)) * pow($T,6) + 2 * pow(10,( - 10)) * pow($T,5) - 3 * pow(10,( - 8)) * pow($T,4) + 2 * pow(10,( - 6)) * pow($T,3) - 0.0025* pow($T,2) - 0.4704* $T + 1131.62;

		}

		return ($EGR);
	}

	public function HOT_WATER($TH)
	{
		$XMU = (-6.325 - 0.033974 * $TH + 2.829 * pow(10,-4) * $TH * $TH - 1.8309 * pow(10,-6) * pow($TH,3.0) + 5.5184 * pow(10,-9) * pow($TH,4.0));
		$MU = exp($XMU);
		$NU = exp(-13.232 - 0.034086 * $TH + 2.9287 * pow(10,-4) * $TH * $TH - 1.9052 * pow(10,-6) * pow($TH,3.0) + 5.8 * pow(10,-9) * pow($TH,4.0));
		$this->ROWH = $MU/$NU;
		$CPH1 = 4.217 - 2.949*pow(10,-3)*$TH + 7.624*pow(10,-5)*$TH*$TH - 7.858*pow(10,-7)*pow($TH,3.0) +3.181*pow(10,-9)*pow($TH,4.0);
		$CPH = $CPH1/4.187;
		$this->CPH1 = $CPH1;
		return $CPH;
	}

	public function EG_SPHT($EGT,$EGX)
	{
		$REM = fmod($EGX, 10);
		$EGX1 = $EGX-$REM;
		$EGRT1 = $this->EG_SPHT1( $EGT, $EGX1);
		$EGX2 = $EGX1+10;
		$EGRT2 = $this->EG_SPHT1($EGT,$EGX2);
		$Y0 = $EGRT1;
		$Y1 = $EGRT2;
		$YY11 = ($EGX - $EGX2) / ($EGX1 - $EGX2) * $Y0;
		$YY22 = ($EGX - $EGX1) / ($EGX2 - $EGX1) * $Y1;
		$YY2 = $YY11 + $YY22;

		return $YY2;
	}

	public function EG_SPHT1($T,$X)
	{
		$EGS = 0.0;

		if($X==0)
		{
			$EGS= $this->HOT_WATER($T) * 4.187;
	
		}
		if($X==10)
		{
			$EGS= 5 * pow(10,( - 8)) * pow($T,2) + 0.0017* $T + 3.937;
		}
		if($X==20)
		{
			$EGS= - 6 * pow(10,( - 10)) * pow($T,3) + pow(10,( - 7)) * pow($T,2) + 0.0023* $T + 3.769;
		}
		if($X==30)
		{
			$EGS= - 1 * pow(10,( - 9)) * pow($T,3) + 2 * pow(10,( - 7)) * pow($T,2) + 0.0028* $T + 3.589;
		}
		if($X==40)
		{
			$EGS=1 * pow(10,( - 9)) * pow($T,3) - 3 * pow(10,( - 7)) * pow($T,2) + 0.0034* $T + 3.4009;
		}
		if($X==50)
		{
			$EGS=2 * pow(10,( - 10)) * pow($T,3) - 5 * pow(10,( - 8)) * pow($T,2) + 0.0039* $T + 3.2033;	
		}
		if($X==60)
		{
			if($T<85)
			{
				$EGS=7 * pow(10,( - 10)) * pow($T,3) - pow(10,( - 7)) * pow($T,2) + 0.0044* $T + 2.997;
			}
			else
			{
				$EGS=0.0044* $T + 2.992;
			}
		}
		if($X==70)
		{
			$EGS= - 2 * pow(10,( - 9)) * pow($T,3) + 3 * pow(10,( - 7)) * pow($T,2) + 0.0048* $T + 2.7818;	
		}
		if($X==80)
		{
			$EGS=0.0053* $T + 2.5564;
		}
		if($X==90)
		{
			$EGS= - 2 * pow(10,( - 19)) * pow($T,3) + 3 * pow(10,( - 17)) * pow($T,2) + 0.0058* $T + 2.322;
		}

		return $EGS;
	}

	public function PG_VISCOSITY($PGT,$PGX)
	{
		$REM= fmod($PGX, 10);
		$PGX1=$PGX-$REM;
		$PGRX1=$this->PG_VISCOSITY1($PGT, $PGX1);
		$PGX2=$PGX1+10;
		$PGRX2=$this->PG_VISCOSITY1($PGT,$PGX2);
		$Y0 = $PGRX1;
		$Y1 = $PGRX2;
		$YY11 = ($PGX - $PGX2) / ($PGX1 - $PGX2) * $Y0;
		$YY22 = ($PGX - $PGX1) / ($PGX2 - $PGX1) * $Y1;
		$YY2 = $YY11 + $YY22;
		return $YY2;
	}

	public function PG_VISCOSITY1($TVS,$X)
	{
		$REM1=fmod($TVS,5);
		$PGT1=(int) ($TVS-$REM1);
		$NX= (int) $X;
		$PGST1 = 0.0;
		$PGST2 = 0.0;
		if($NX==0)
		{
			$PGST1=(exp(-6.325 - 0.033974 * $PGT1 + 0.0002829 * $PGT1 * $PGT1 - 0.0000018309 * pow($PGT1,3) + 0.0000000055184 * pow($PGT1,4)))*1000;
		}
		else
		{
			$PGST1=$this->PEG($PGT1,$NX);
		}
		$PGT2=$PGT1+5;
		if($NX==0)
		{
			$PGST2=(exp(-6.325 - 0.033974 * $PGT2 + 0.0002829 * $PGT2 * $PGT2 - 0.0000018309 *pow($PGT2,3) + 0.0000000055184 * pow($PGT2,4)))*1000;
		}
		else
		{
			$PGST2=$this->PEG($PGT2, $NX);
		}
		$NY0 = $PGST1;
		$NY1 = $PGST2;
		$NYY11 = ($TVS - $PGT2) / ($PGT1 - $PGT2) * $NY0;
		$NYY22 = ($TVS - $PGT1) / ($PGT2 - $PGT1) * $NY1;
		$NYY2 = $NYY11 + $NYY22;
		return $NYY2;
	}

	public function PG_THERMAL_CONDUCTIVITY($PGT,$PGX)
	{
		$REM= fmod($PGX, 10);
		$PGX1=$PGX-$REM;
		$PGRX1=$this->PG_THERMAL_CONDUCTIVITY1($PGT, $PGX1);
		$PGX2=$PGX1+10;
		$PGRX2=$this->PG_THERMAL_CONDUCTIVITY1($PGT,$PGX2);
		$Y0 = $PGRX1;
		$Y1 = $PGRX2;
		$YY11 = ($PGX - $PGX2) / ($PGX1 - $PGX2) * $Y0;
		$YY22 = ($PGX - $PGX1) / ($PGX2 - $PGX1) * $Y1;
		$YY2 = $YY11 + $YY22;
		return $YY2;
	}

	public function PG_THERMAL_CONDUCTIVITY1($TVS,$X)
	{		
		$REM1=fmod($TVS,5);
		$PGT1= (int) ($TVS-$REM1);
		$NX=(int)$X;
		$PGST1 = 0.0;
		$PGST2 = 0.0;
		if($NX==0)
		{
			$PGST1=0.5628+ 0.00197* $PGT1 - 0.000008298* $PGT1 *$PGT1;
		}
		else
		{
			$PGST1=$this->PTH($PGT1,$NX);
		}
		$PGT2=$PGT1+5;
		if($NX==0)
		{
			$PGST2=0.5628+ 0.00197* $PGT2 - 0.000008298* $PGT2 *$PGT2;
		}
		else
		{
			$PGST2=$this->PTH($PGT2,$NX);
		}

		$NY0 = $PGST1;
		$NY1 = $PGST2;
		$NYY11 = ($TVS - $PGT2) / ($PGT1 - $PGT2) * $NY0;
		$NYY22 = ($TVS - $PGT1) / ($PGT2 - $PGT1) * $NY1;
		$NYY2 = $NYY11 + $NYY22;
		return $NYY2;
	}

	public function PG_ROW($PGT,$PGX)
	{
		$REM=fmod($PGX , 10);
		$PGX1=$PGX-$REM;
		$PGRT1=$this->PG_ROW1($PGT, $PGX1);
		$PGX2=$PGX1+10;
		$PGRT2=$this->PG_ROW1($PGT,$PGX2);
		$Y0 = $PGRT1;
		$Y1 = $PGRT2;

		$YY11 = ($PGX - $PGX2) / ($PGX1 - $PGX2) * $Y0;
		$YY22 = ($PGX - $PGX1) / ($PGX2 - $PGX1) * $Y1;
		$YY2 = $YY11 + $YY22;

		return $YY2;
	}

	public function PG_ROW1($T,$X)
	{
		$PGS = 0.0; 
		$PGR = 0.0;

		if($X==0)
		{
			$PGS=$this->HOT_WATER($T) * 4.187;
			$PGR=$this->ROWH;
		}
		if($X==10)
		{
			$PGR= - 0.0000000000005* pow($T,6)  +  0.0000000002* pow($T,5)  -  0.00000003* pow($T,4)  +  0.000002* pow($T,3)  -  0.0025* pow($T,2)  -  0.236* $T  +  1013.9;
	
		}
		if($X==20)
		{
			$PGR= - 0.0000000000005* pow($T,6)  +  0.0000000002* pow($T,5)  -  0.00000003* pow($T,4)  +  0.000002* pow($T,3)  -  0.0026* pow($T,2)  -  0.2902* $T  +  1025.8;
		}
		if($X==30)
		{
			if($T<105)
			{
	
				$PGR=0.0000000000003* pow($T,6)  -  0.0000000001* pow($T,5)  +  0.00000002* pow($T,4)  -  0.000001* pow($T,3)  -  0.0025* pow($T,2)  -  0.3441* $T  +  1036.2;
			}
			else
			{
				$PGR= - 0.9308* $T  +  1069.9;
			}
		}
		if($X==40)
		{
			if($T<105)
			{
				$PGR= 0.0000000000009* pow($T,6)  -  0.0000000003* pow($T,5)  +  0.00000004* pow($T,4)  -  0.000002* pow($T,3)  -  0.0025* pow($T,2)  -  0.393* $T +  1045.1;
			}
			else
			{
				$PGR= - 0.984* $T  +  1079;
			}
		}
		if($X==50)
		{
			if($T<105)
			{
				$PGR=0.000000000001* pow($T,6)  -  0.0000000004* pow($T,5)  +  0.00000005* pow($T,4)  -  0.000004* pow($T,3)  -  0.0024* pow($T,2)  -  0.442* $T  +  1052.7;
			}
			else
			{
				$PGR= - 1.0306* $T + 1086.5;
			}
		}
		if($X==60)
		{
			if($T<105)
			{
				$PGR=  - 0.0000000000003* pow($T,6)  +  0.00000000009* pow($T,5)  -  0.00000001* pow($T,4)  +  0.0000008* pow($T,3)  -  0.0026* pow($T,2)  -  0.4866* $T  +  1059;
			}
			else
			{
				$PGR=  - 1.0706* $T  +  1092.4;
			}
	
		}
		if($X==70)
		{
			if($T<105)
			{
				$PGR= 0.00000000000007* pow($T,6)  -  0.00000000004* pow($T,5)  +  0.000000008* pow($T,4) -  0.0000009* pow($T,3)  -  0.0024* pow($T,2)  -  0.5334* $T  +  1064.1;
			}
			else
			{
				$PGR=  - 1.1044* $T  +  1096.8;
			}
	
		}
		if($X==80)
		{
			if($T<80)
			{
				$PGR=  0.00000002* pow($T,3)  -  0.0005* pow($T,2)  -  0.7591* $T  +  1068.5;
			}
			else
			{
				$PGR=   - 0.8538* $T  +  1073.2;
			}
		}
		if($X==90)
		{

			$PGR=0.0000000000005* pow($T,6)  -  0.0000000002* pow($T,5)  +  0.00000003* pow($T,4)  -  0.000002* pow($T,3) -  0.0004* pow($T,2)  -  0.7601* $T  +  1066.5;

		}

		return ($PGR);
	}

	public function PG_SPHT($PGT,$PGX)
	{
		$REM= fmod($PGX, 10);
		$PGX1=$PGX-$REM;
		$PGRT1=$this->PG_SPHT1($PGT, $PGX1);
		$PGX2=$PGX1+10;
		$PGRT2=$this->PG_SPHT1($PGT,$PGX2);
		$Y0 = $PGRT1;
		$Y1 = $PGRT2;
		$YY11 = ($PGX - $PGX2) / ($PGX1 - $PGX2) * $Y0;
		$YY22 = ($PGX - $PGX1) / ($PGX2 - $PGX1) * $Y1;
		$YY2 = $YY11 + $YY22;
		return $YY2;
	}

	public function PG_SPHT1($T,$X)
	{
		$PGS = 0.0;
		$PGR = 0.0;

		if($X==0)
		{
			$PGS=$this->HOT_WATER($T)*4.187;
			$PGR=$this->CPH1;
		}
		if($X==10)
		{
			if($T<15)
			{
				$PGS=0.0016* $T + 4.042;
			}
			else
			{
				$PGS=0.0016* $T + 4.043;
			}
		}
		if($X==20)
		{
			if($T<25)
			{
				$PGS=0.0022* $T + 3.9289;
			}
			else
			{
				$PGS=0.0022* $T + 3.9286;
			}
		}
		if($X==30)
		{
			if($T<30)
			{
				$PGS=0.00000000000004* pow($T,6) - 0.00000000001* pow($T,5)+ 0.000000002* pow($T,4) - 0.0000001* pow($T,3) + 0.000004* pow($T,2) + 0.0027* $T + 3.7931;
			}
			else
			{
				$PGS=0.000000004* pow($T,3) - 0.000001* pow($T,2) + 0.0028* $T + 3.7912;
			}

		}
		if($X==40)
		{
			$PGS=0.00000006* pow($T,2) + 0.0033* $T + 3.6359;
		}	
		if($X==50)
		{
			if($T<55)
			{
				$PGS=  0.000000002* pow($T,2) + 0.0039* $T + 3.4547;
			}
			else
			{
				$PGS=0.000000003* pow($T,3) - 0.0000008* pow($T,2) + 0.0039* $T + 3.4529;
			}
		}
		if($X==60)
		{
			$PGS= 0.000000003* pow($T,3) - 0.0000005* pow($T,2) + 0.0044* $T + 3.2503;	
		}
		if($X==70)
		{
			$PGS=0.00000001* pow($T,2) + 0.005* $T  + 3.0176;	
		}
		if($X==80)
		{
			$PGS= 0.00000006* pow($T,2) + 0.0055* $T + 2.7657;
		}
		if($X==90)
		{
			$PGS= 0.000000002* pow($T,3) - 0.0000004* pow($T,2) + 0.0061* $T + 2.478;
		}

		return $PGS;
	}


}