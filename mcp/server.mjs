#!/usr/bin/env node
import readline from 'node:readline';

const version = '1.0.0';
const site = (process.env.SEEHEADER_SITE_URL || '').replace(/\/$/, '');
const user = process.env.SEEHEADER_USER || '';
const password = process.env.SEEHEADER_APP_PASSWORD || '';
const info = {name:'seeheader-wordpress',version};
const tools = [
  {name:'seeheader_get_config',description:'Read the current header settings and built menu from WordPress.',inputSchema:{type:'object',properties:{},additionalProperties:false},annotations:{readOnlyHint:true}},
  {name:'seeheader_list_icons',description:'List SVG icons discovered dynamically from the SeeHeader icon directory. Filter by category or search text.',inputSchema:{type:'object',properties:{search:{type:'string'},category:{type:'string'}},additionalProperties:false},annotations:{readOnlyHint:true}},
  {name:'seeheader_update_config',description:'Update selected header settings. Other settings are preserved. Use seeheader_get_config first to inspect current values.',inputSchema:{type:'object',properties:{changes:{type:'object',description:'SeeHeader settings to update, such as theme, colors, button style, and visibility.'}},required:['changes'],additionalProperties:false}},
  {name:'seeheader_replace_menu',description:'Replace the plugin built menu. Each item needs a unique id, label, optional url, parent id, icon path, mega flag, column, description and badge.',inputSchema:{type:'object',properties:{items:{type:'array',items:{type:'object',properties:{id:{type:'string'},label:{type:'string'},url:{type:'string'},parent:{type:'string'},icon:{type:'string'},mega:{type:'boolean'},column:{type:'integer'},description:{type:'string'},badge:{type:'string'},image:{type:'string'},new_tab:{type:'boolean'}}},maxItems:200}},required:['items'],additionalProperties:false}},
];

async function wordpress(path,method='GET',body){
  if(!site||!user||!password)throw new Error('Set SEEHEADER_SITE_URL, SEEHEADER_USER and SEEHEADER_APP_PASSWORD for this MCP server.');
  const url = new URL(site+'/wp-json/seeheader/v1/'+path);
  if(url.protocol!=='https:'&&!['localhost','127.0.0.1'].includes(url.hostname))throw new Error('The WordPress URL must use HTTPS.');
  const response=await fetch(url,{method,headers:{Authorization:'Basic '+Buffer.from(user+':'+password).toString('base64'),'Content-Type':'application/json'},body:body===undefined?undefined:JSON.stringify(body),signal:AbortSignal.timeout(15000)});
  const value=await response.json().catch(()=>null);
  if(!response.ok)throw new Error((value&&value.message)||'WordPress returned HTTP '+response.status);
  return value;
}
async function call(name,args={}){
  if(name==='seeheader_get_config')return wordpress('config');
  if(name==='seeheader_list_icons'){
    const list=await wordpress('icons');
    const search=String(args.search||'').toLowerCase(),category=String(args.category||'').toLowerCase();
    return list.filter(icon=>(!category||icon.category.toLowerCase()===category)&&(!search||(icon.path+' '+icon.name+' '+icon.category).toLowerCase().includes(search)));
  }
  if(name==='seeheader_update_config'){
    if(!args.changes||typeof args.changes!=='object'||Array.isArray(args.changes))throw new Error('changes must be an object.');
    return wordpress('config','POST',args.changes);
  }
  if(name==='seeheader_replace_menu'){
    if(!Array.isArray(args.items))throw new Error('items must be an array.');
    return wordpress('config','POST',{menu_source:'builder',nav_items:args.items});
  }
  throw new Error('Unknown tool: '+name);
}
function send(id,result,error){
  const packet={jsonrpc:'2.0',id};
  if(error)packet.error={code:-32603,message:error.message||String(error)};else packet.result=result;
  process.stdout.write(JSON.stringify(packet)+'\n');
}
async function handle(message){
  if(message.id===undefined)return;
  try{
    if(message.method==='initialize')return send(message.id,{protocolVersion:message.params?.protocolVersion||'2025-11-25',capabilities:{tools:{}},serverInfo:info});
    if(message.method==='server/discover')return send(message.id,{protocolVersion:'2026-07-28',capabilities:{tools:{}},serverInfo:info});
    if(message.method==='ping')return send(message.id,{});
    if(message.method==='tools/list')return send(message.id,{tools});
    if(message.method==='tools/call'){
      const value=await call(message.params?.name,message.params?.arguments||{});
      return send(message.id,{content:[{type:'text',text:JSON.stringify(value)}],structuredContent:{result:value}});
    }
    send(message.id,null,new Error('Method not found: '+message.method));
  }catch(error){
    if(message.method==='tools/call')send(message.id,{content:[{type:'text',text:error.message}],isError:true});
    else send(message.id,null,error);
  }
}
const input=readline.createInterface({input:process.stdin,crlfDelay:Infinity});
input.on('line',line=>{try{void handle(JSON.parse(line));}catch(error){process.stderr.write('Invalid MCP JSON: '+error.message+'\n');}});
