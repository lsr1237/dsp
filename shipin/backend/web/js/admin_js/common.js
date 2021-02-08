		
		//全选/反选
		function CheckBox(obj){
			if(obj.is(':checked')){
				$(":checkbox").prop("checked",true)
			}else{
				$(":checkbox").prop("checked",false)
			}
		}
		//批量删除
		 function IsAlldelete(url){
				if($("[name=select]:checked").val()==null){
						layer.msg('请勾选要删除的类目',1,5);
				}else{
						var str=''
						$("[name=select]:checked").each(function(){
						str=str+$(this).val()+','			  
				})
						str = str.substring(0, str.length - 1);
						msg(str,url)
				}
			}
		//单个删除
		 function Isdelete(str,url){
				msg(str,url)
		}

	//排序
		function isPx(url){
			
		}
	//layer插件
	function msg(obj,url){
			$.layer({
				shade : [0.5 , '#000' , true],
				area : ['auto','auto'],
				dialog : {
					msg:'你确定要删除?',
					btns : 2, 
					type : 4,
					btn : ['确定','取消'],
					yes : function(){
						$('#rightMain', parent.document).attr("src",url+'?id='+obj)
					}
				}
			});	 
			
		}