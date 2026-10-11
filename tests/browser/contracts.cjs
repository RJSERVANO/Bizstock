const fs=require('fs'),assert=require('assert/strict');const doc=JSON.parse(fs.readFileSync('docs/openapi.yaml','utf8'));
function validate(schema,value,label='response'){
 if(schema.$ref)return validate(schema.$ref.slice(2).split('/').reduce((o,k)=>o[k],doc),value,label);
 if(value===null&&schema.nullable)return;
 if(schema.oneOf){const matches=schema.oneOf.filter(s=>{try{validate(s,value,label);return true;}catch{return false;}});assert.equal(matches.length,1,`${label}: exactly one schema must match`);return;}
 if(schema.type==='object'){assert(value!==null&&typeof value==='object'&&!Array.isArray(value),label);for(const key of schema.required||[])assert(Object.hasOwn(value,key),`${label} missing ${key}`);for(const [key,v] of Object.entries(value)){if(schema.properties?.[key])validate(schema.properties[key],v,`${label}.${key}`);else if(schema.additionalProperties===false)assert.fail(`${label} unexpected ${key}`);else if(typeof schema.additionalProperties==='object')validate(schema.additionalProperties,v,`${label}.${key}`);}if(schema.minProperties)assert(Object.keys(value).length>=schema.minProperties,label);}
 else if(schema.type==='array'){assert(Array.isArray(value),label);value.forEach((v,i)=>validate(schema.items,v,`${label}[${i}]`));}
 else if(schema.type==='integer'){assert(Number.isInteger(value),label);if(schema.minimum!==undefined)assert(value>=schema.minimum,label);if(schema.maximum!==undefined)assert(value<=schema.maximum,label);}
 else if(schema.type==='string'){assert.equal(typeof value,'string',label);if(schema.pattern)assert(new RegExp(schema.pattern).test(value),label);if(schema.maxLength)assert([...value].length<=schema.maxLength,label);}
 else if(schema.type==='boolean')assert.equal(typeof value,'boolean',label);
 if(schema.enum)assert(schema.enum.includes(value),label);
}
module.exports=(path,method,json,status=200)=>{const op=doc.paths[path][method];assert.equal(op['x-implementation-status'],'implemented');const response=op.responses[String(status)];assert(response,`${path} status ${status}`);validate(response.content['application/json'].schema,json,path);};
