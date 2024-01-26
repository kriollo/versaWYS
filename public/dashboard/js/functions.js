export const existeCookieBuild=()=>void 0!==document.cookie.split(";").find((t=>t.trim().startsWith("debug")));export const versaFetch=async t=>{const{url:e,method:a,headers:r,data:o,credentials:n="same-origin"}=t,s={method:a,headers:r||{},credentials:n};o&&(s.body=o);try{const t=await fetch(e,s),a=t.headers.get("Content-Type")?.includes("application/json");if(!function(t){let e=!0;return new Map([[400,"El Servidor no pudo procesar la solicitud"],[401,"No está autorizado para acceder a este recurso"],[403,"No tiene permisos para realizar esta acción"],[404,"Recurso no encontrado"],[500,"Error interno del servidor"],[503,"Servicio no disponible"],[422,"No se pudo procesar la solicitud"],[429,"Demasiadas solicitudes, intente de nuevo más tarde"],[504,"El tiempo de espera para el servicio ha sido excedido"]]).has(t)&&(e=!1),e}(t.status)){if(t.headers.get("Content-Type")?.includes("application/json")){return await t.json()}if(t.headers.get("Content-Type")?.includes("text/html")){const e=await t.text();throw new Error(e)}}return a?await t.json():await t.text()}catch(t){return{success:0,message:t.message}}};export const getDateToday=()=>{const t=new Date;return`${t.getFullYear()}-${String(t.getMonth()+1).padStart(2,"0")}-${String(t.getDate()).padStart(2,"0")}`};export const getDateTimeToday=()=>{const t=new Date;return`${t.getFullYear()}-${String(t.getMonth()+1).padStart(2,"0")}-${String(t.getDate()).padStart(2,"0")} ${String(t.getHours()).padStart(2,"0")}:${String(t.getMinutes()).padStart(2,"0")}:${String(t.getSeconds()).padStart(2,"0")}`};export const getTime=()=>{const t=new Date;return`${String(t.getHours()).padStart(2,"0")}:${String(t.getMinutes()).padStart(2,"0")}:${String(t.getSeconds()).padStart(2,"0")}`};export const versaAlert=t=>{const{title:e="¡Éxito!",message:a="",html:r="",type:o="success",AutoClose:n=!0,callback:s}=t;Swal.fire({title:e,text:a,html:r,icon:o,confirmButtonText:"Aceptar",allowOutsideClick:!0,allowEscapeKey:!0,allowEnterKey:!0,timer:n?3e3:null}).then((t=>{t&&s&&s()}))};