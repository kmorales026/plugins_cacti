function selectAll(field) {
	checkboxes = document.getElementsByName('graphs[]');
	if( (checkboxes.length == 0) ){
		checkbox = document.getElementById('graphs_select_all');
		alert("No graphs available");
		checkbox.checked = false;
	}else{
		for(var i in checkboxes){
			checkboxes[i].checked = field.checked;
		}
	}
}

function calculate(index, graphs, original, option){

	var array = new Array();
	
	for(var k=0;k<graphs;k++){
		array[k] = new Array();
		for(var i=0;i<original.length;i++){
			array[k][i]= original[i];
		}
	}
	
	if(option.value==3){ //Divide by 1000
		var operand = 0.001;
		var suffix = "k";
	}else if(option.value==4){ //Divide by 1000000
		var operand = 0.000001;
		var suffix = "M";
	}else if(option.value==5){ //Divide by 1000000000
		var operand = 0.000000001;
		var suffix = "G";
	}else if(option.value==6){ //Multiply by 1000
		var operand = 1000;
		var suffix = "m";
	}else if(option.value==7){ //Multiply by 1000000
		var operand = 1000000;
		var suffix = "u";
	}else if(option.value==8){ //Multiply by 1000000000
		var operand = 1000000000;
		var suffix = "n";
	}else if(option.value==9){ //Multiply by 1000000000
		var operand = 8;
	}else if(option.value==10){ //Multiply by 1000000000
		var operand = 0.125;
	}
	
	for(var i = 0; i < original.length; i++){
		var sanitize = document.getElementById('graph_'+index+'&values_'+i).innerHTML.split(" "); //sanitize
		document.getElementById('graph_'+index+'&values_'+i).innerHTML = sanitize[0]; //sanitize
		
		var current_value = document.getElementById('graph_'+index+'&values_'+i);
		
		if(option.value == 0){//Back to default values
			current_value.innerHTML = array[index][i];
		}else if(option.value == 1){//Transform automatically based on value
			var operand = magic_scale(current_value.innerHTML);
			var suffix = magic_suffix(current_value.innerHTML);
			if(current_value<1){
				current_value.innerHTML = remove_zero_decimals(parseFloat(current_value.innerHTML/operand).toFixed(2)) + " " + suffix;
			}else{
				current_value.innerHTML = remove_zero_decimals(parseFloat(current_value.innerHTML/operand).toFixed(2)) + " " + suffix;
			}
		}else if(option.value == 2){ //IEC TO SI
			var operand = iec_transform(current_value.innerHTML);
			if(current_value<1){
				current_value.innerHTML = remove_zero_decimals(parseFloat(current_value.innerHTML*operand).toFixed(2));
				if("suffix" in window)
					current_value.innerHTML += suffix;
			}else{
				current_value.innerHTML = remove_zero_decimals(parseFloat(current_value.innerHTML*operand).toFixed(2));
				if("suffix" in window)
					current_value.innerHTML += suffix;
			}
		}else if( (option.value>=3) && (option.value<=8) ){ //Divide|Multiply
			var suffix = magic_suffix(current_value.innerHTML);
			if(current_value < 1){
				current_value.innerHTML = remove_zero_decimals(parseFloat(current_value.innerHTML*operand).toFixed(2)) + " " + suffix;
			}else{
				current_value.innerHTML = remove_zero_decimals(parseFloat(current_value.innerHTML*operand).toFixed(2)) + " " + suffix;
			}
		}else if( (option.value==9) || (option.value==10) ){ //Bytes|Bits
			current_value.innerHTML = current_value.innerHTML * operand;
		}
	}
}

function remove_zero_decimals(input){
	var split = input.toString().split(".");
	if(split[1] == 00){
		var result = "";
	}else{
		var result = "." + split[1];
	}
	return split[0] + result;
}

function count_zeros(input, direction){ //direction is 1 for left-to-right progression; -1 for right-to-left progression
	var count = 0;
	for(var i=0;i<(input.toString().length);i++){
		var zero_occurrence = input.substr(i,( 1 * (direction) ));
		if(zero_occurrence==0){
			count++;
		}else{
			break;
		}
	}
	return count;
}

function magic(input){
	if( !(input == parseInt(input)) ){ //If number has a floating point
		var split = input.toString().split(".");
		if(input==0){
			var result = 0;
		}else if(input>=1){ //If number is greater than 1.0000
			var result = split[0].toString().length;
		}else{ //If number between 0 and 1 (ex: 0.123123)
			var result = count_zeros(split[1], 1) + 1;
		}
	}else{ //If number is an integer
		var result = input.toString().length;
	}
	
	return result;
}

function iec_transform(input){
	var result = magic(input);
	
	if( (result % 3) != 0 ){
		var output = parseInt(result/3);
	}else if( (result % 3) == 0){
		var output = (result / 3) - 1;
	}
	if(input>=1){
		var final_result = Math.pow(1000, parseInt(output)) / Math.pow(1024, parseInt(output));
	}else{
		var final_result = ( 1 / ( (Math.pow(1000, (output+1))) / (Math.pow(1024, parseInt(output+1))) ) );
	}
	
	return final_result;
}

function magic_scale(input){	

	var absolute = Math.abs(input);
	var result = magic(absolute);		
	
	if( (result % 3) != 0 ){
		var output = parseInt(result/3);
	}else if( (result % 3) == 0){
		var output = (result / 3) - 1;
	}
	if(absolute>=1){
		var final_result = Math.pow(1000, output);
	}else{
		var final_result = ( 1 / (Math.pow(1000, (output+1))) );
	}
	return final_result;
}

function magic_suffix(input){
	
	absolute = Math.abs(input);
	if( (result==0) ){ //less than 999; bigger than 0
		var suffix = "";
	}
	else if( (absolute>=1) ){
		var result = magic(absolute);
		if((result>=1) && (result<=3)){
			var suffix = "";
		}
		else if( (result>=4) && (result<=6) ){
			var suffix = "k";
		}else if( (result>=7) && (result<=9) ){
			var suffix = "M";
		}else if( (result>=10) && (result<=12) ){
			var suffix = "G";
		}else if( (result>12) ){
			var suffix = "T";
		}
	}else if( (absolute>=0) && (absolute<1) ){
		if( !(absolute == parseInt(absolute)) ){ //If number has a floating point
			var split = absolute.toString().split(".");
			var result = count_zeros(split[1], 1);
			if( (result>=0) && (result<=2) ){
				var suffix = "m";
			}else if( (result>=3) && (result<=5) ){ 
				var suffix = "u";
			}else if( (result>=6) && (result<=8) ){ 
				var suffix = "n";
			}else if( (result>=9) && (result<=11) ){
				var suffix = "p";
			}
		}else{
			suffix = "";
		}
	}

	return suffix;
}