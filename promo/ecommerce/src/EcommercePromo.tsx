import React from 'react';
import {
  AbsoluteFill,
  Easing,
  interpolate,
  Sequence,
  spring,
  useCurrentFrame,
  useVideoConfig,
} from 'remotion';
import {
  Armchair,
  Box,
  Check,
  ChevronRight,
  Clock3,
  CreditCard,
  Heart,
  MapPin,
  PackageCheck,
  Search,
  ShoppingBag,
  SlidersHorizontal,
  Sparkles,
  Star,
  Truck,
} from 'lucide-react';
import {loadFont} from '@remotion/google-fonts/Inter';
import './styles.css';

const {fontFamily} = loadFont();
const orange = '#f56617';
const dark = '#111827';

const fade = (frame: number, duration: number) =>
  interpolate(frame, [0, 18, duration - 18, duration], [0, 1, 1, 0], {
    extrapolateLeft: 'clamp',
    extrapolateRight: 'clamp',
  });

const rise = (frame: number, fps: number, delay = 0) => {
  const value = spring({frame: frame - delay, fps, config: {damping: 16, stiffness: 110}});
  return {opacity: value, transform: `translateY(${(1 - value) * 54}px)`};
};

const Brand: React.FC<{light?: boolean}> = ({light = false}) => (
  <div className="brand">
    <div className="brandMark"><Armchair size={27} strokeWidth={2.7}/></div>
    <span style={{color: light ? 'white' : dark}}>Furni<span style={{color: orange}}>Sync</span></span>
  </div>
);

const Caption: React.FC<{eyebrow: string; title: React.ReactNode; body: string; frame: number}> = ({eyebrow, title, body, frame}) => {
  const {fps} = useVideoConfig();
  return (
    <div className="caption">
      <div className="eyebrow" style={rise(frame, fps)}><Sparkles size={19}/>{eyebrow}</div>
      <h2 style={rise(frame, fps, 5)}>{title}</h2>
      <p style={rise(frame, fps, 10)}>{body}</p>
    </div>
  );
};

const Browser: React.FC<{children: React.ReactNode; frame: number; className?: string}> = ({children, frame, className = ''}) => {
  const {fps} = useVideoConfig();
  const scale = spring({frame, fps, config: {damping: 18, stiffness: 90}});
  return (
    <div className={`browser ${className}`} style={{opacity: scale, transform: `scale(${0.9 + scale * 0.1}) translateY(${(1 - scale) * 50}px)`}}>
      <div className="browserTop"><i/><i/><i/><div className="url">furnisync.ph/shop</div></div>
      <div className="browserBody">{children}</div>
    </div>
  );
};

const ProductVisual: React.FC<{kind?: 'chair' | 'sofa' | 'table'; color?: string}> = ({kind = 'chair', color = '#e7a36c'}) => (
  <div className={`furniture ${kind}`} style={{'--furniture': color} as React.CSSProperties}>
    <div className="furnitureBack"/><div className="furnitureSeat"/><div className="leg one"/><div className="leg two"/>
  </div>
);

const Opening: React.FC = () => {
  const frame = useCurrentFrame();
  const {fps, durationInFrames} = useVideoConfig();
  const pulse = 1 + Math.sin(frame / 24) * 0.025;
  return (
    <AbsoluteFill className="scene opening" style={{opacity: fade(frame, durationInFrames)}}>
      <div className="orb orbOne"/><div className="orb orbTwo"/>
      <div className="openingCopy">
        <div style={rise(frame, fps)}><Brand light/></div>
        <h1 style={rise(frame, fps, 8)}>Furniture shopping,<br/><em>reimagined.</em></h1>
        <p style={rise(frame, fps, 16)}>Discover, explore, and order from trusted local furniture stores—all in one seamless experience.</p>
        <div className="cta" style={rise(frame, fps, 24)}>Explore FurniSync <ChevronRight size={23}/></div>
      </div>
      <div className="heroStage" style={{transform: `scale(${pulse}) rotate(-3deg)`}}>
        <div className="heroHalo"/><ProductVisual kind="sofa" color="#f6d0ae"/>
        <div className="floatingCard stock"><Check size={19}/> In stock nearby</div>
        <div className="floatingCard rating"><Star size={18} fill="#f59e0b"/> 4.9 customer rating</div>
      </div>
    </AbsoluteFill>
  );
};

const products = [
  {name: 'Luna Lounge Chair', price: '₱12,490', kind: 'chair' as const, color: '#d88b50'},
  {name: 'Nordic Cloud Sofa', price: '₱28,900', kind: 'sofa' as const, color: '#b9c2b2'},
  {name: 'Oslo Coffee Table', price: '₱8,250', kind: 'table' as const, color: '#98623f'},
];

const Browse: React.FC = () => {
  const frame = useCurrentFrame();
  const {durationInFrames} = useVideoConfig();
  return (
    <AbsoluteFill className="scene pale" style={{opacity: fade(frame, durationInFrames)}}>
      <Caption frame={frame} eyebrow="Discover with confidence" title={<>Your next favorite piece is <span>closer than you think.</span></>} body="Browse curated collections, local stores, flash deals, and furniture that is actually available."/>
      <Browser frame={frame} className="browseBrowser">
        <div className="shopNav"><Brand/><div className="search"><Search size={18}/>Search furniture, stores, and styles</div><Heart/><ShoppingBag/></div>
        <div className="filters"><b>Shop furniture</b><span>Living Room</span><span>Bedroom</span><span>Dining</span><span>Office</span><button><SlidersHorizontal size={15}/> Filters</button></div>
        <div className="productGrid">
          {products.map((product, index) => (
            <div className="productCard" key={product.name} style={{transform: `translateY(${Math.max(0, 38 - frame * 0.45 - index * 8)}px)`, opacity: interpolate(frame, [18 + index * 6, 40 + index * 6], [0, 1], {extrapolateLeft: 'clamp', extrapolateRight: 'clamp'})}}>
              <div className="productImage"><ProductVisual kind={product.kind} color={product.color}/><span>{index === 1 ? 'BESTSELLER' : index === 2 ? 'NEW' : '20% OFF'}</span></div>
              <small>Local collection</small><h3>{product.name}</h3><div className="ratingRow"><Star size={13} fill="#f59e0b"/> 4.{9-index} <b>{product.price}</b></div>
            </div>
          ))}
        </div>
      </Browser>
    </AbsoluteFill>
  );
};

const Explore3D: React.FC = () => {
  const frame = useCurrentFrame();
  const {durationInFrames} = useVideoConfig();
  const rotation = interpolate(frame, [25, 260], [-12, 18], {extrapolateLeft: 'clamp', extrapolateRight: 'clamp'});
  return (
    <AbsoluteFill className="scene darkScene" style={{opacity: fade(frame, durationInFrames)}}>
      <div className="threeDCopy"><Caption frame={frame} eyebrow="Interactive 3D preview" title={<>See every angle.<br/><span>Love every detail.</span></>} body="Rotate furniture in 3D before you buy, inspect finishes, and choose with greater confidence."/></div>
      <div className="viewer">
        <div className="viewerHeader"><span>Nordic Cloud Sofa</span><div><button className="active">3D View</button><button>Photos</button></div></div>
        <div className="viewerCanvas">
          <div className="gridFloor"/>
          <div className="rotator" style={{transform: `perspective(900px) rotateY(${rotation}deg)`}}><ProductVisual kind="sofa" color="#dbb58f"/></div>
          <div className="dragHint">↔ Drag to rotate in 3D</div>
          <div className="materialChip"><i/><div><small>Selected finish</small><b>Warm Sand</b></div></div>
        </div>
      </div>
    </AbsoluteFill>
  );
};

const Compare: React.FC = () => {
  const frame = useCurrentFrame();
  const {durationInFrames, fps} = useVideoConfig();
  const line = spring({frame: frame - 40, fps, config: {damping: 20}});
  return (
    <AbsoluteFill className="scene compareScene" style={{opacity: fade(frame, durationInFrames)}}>
      <div className="compareTop" style={rise(frame, fps)}><span>Compare smarter</span><h2>The right fit, side by side.</h2><p>Compare price, dimensions, materials, ratings, and availability without jumping between stores.</p></div>
      <div className="compareTable" style={{...rise(frame, fps, 10)}}>
        <div className="compareLabel"><div/><b>Price</b><b>Material</b><b>Width</b><b>Availability</b><b>Rating</b></div>
        {products.slice(0, 2).map((product, index) => <div className={`compareColumn ${index === 0 ? 'winner' : ''}`} key={product.name}>
          {index === 0 && <span className="best">BEST MATCH</span>}
          <div className="miniProduct"><ProductVisual kind={product.kind} color={product.color}/><strong>{product.name}</strong></div>
          <b className="price">{product.price}</b><span>{index ? 'Linen blend' : 'Premium fabric'}</span><span>{index ? '218 cm' : '196 cm'}</span><span className="available"><Check size={16}/> In stock</span><span>★ {index ? '4.8' : '4.9'} / 5</span>
        </div>)}
        <div className="scanLine" style={{transform: `scaleX(${line})`}}/>
      </div>
    </AbsoluteFill>
  );
};

const Checkout: React.FC = () => {
  const frame = useCurrentFrame();
  const {durationInFrames, fps} = useVideoConfig();
  const check = spring({frame: frame - 135, fps, config: {damping: 12}});
  return (
    <AbsoluteFill className="scene pale checkoutScene" style={{opacity: fade(frame, durationInFrames)}}>
      <Caption frame={frame} eyebrow="Easy, secure checkout" title={<>From cart to confirmed<br/><span>in just a few steps.</span></>} body="Choose delivery, apply store vouchers, and complete your purchase with a clear order summary."/>
      <div className="checkoutCard" style={rise(frame, fps, 8)}>
        <div className="steps"><span className="done"><Check/> Cart</span><i/><span className="done"><Check/> Delivery</span><i/><span className="current">3</span><b>Payment</b></div>
        <div className="checkoutColumns">
          <div className="payment"><h3>Payment details</h3><label>Cardholder name</label><div className="input">Maria Santos</div><label>Card number</label><div className="input"><CreditCard size={20}/>•••• •••• •••• 4821</div><div className="row"><div><label>Expiry</label><div className="input">08 / 29</div></div><div><label>CVV</label><div className="input">•••</div></div></div><button>Pay ₱13,240.00 <ChevronRight size={19}/></button></div>
          <div className="summary"><h3>Order summary</h3><div className="summaryProduct"><div><ProductVisual/></div><p><b>Luna Lounge Chair</b><small>Warm tan · Qty 1</small></p><strong>₱12,490</strong></div><div className="total"><span>Subtotal<b>₱12,490</b></span><span>Delivery<b>₱750</b></span><hr/><span className="grand">Total<b>₱13,240</b></span></div></div>
        </div>
        <div className="successOverlay" style={{opacity: check, transform: `scale(${0.85 + check * 0.15})`}}><div><Check size={50}/></div><h3>Order confirmed!</h3><p>Your furniture is getting ready.</p></div>
      </div>
    </AbsoluteFill>
  );
};

const Track: React.FC = () => {
  const frame = useCurrentFrame();
  const {durationInFrames, fps} = useVideoConfig();
  const progress = interpolate(frame, [20, 160], [0, 1], {extrapolateLeft: 'clamp', extrapolateRight: 'clamp', easing: Easing.out(Easing.cubic)});
  return (
    <AbsoluteFill className="scene trackingScene" style={{opacity: fade(frame, durationInFrames)}}>
      <div className="trackingHead" style={rise(frame, fps)}><span>Order #FS-2048</span><h2>Stay updated, every step of the way.</h2></div>
      <div className="trackingCard" style={rise(frame, fps, 8)}>
        <div className="deliveryTitle"><div><Truck size={28}/></div><p><small>Estimated arrival</small><b>Tomorrow, 2:00–5:00 PM</b></p><span>On the way</span></div>
        <div className="timeline"><div className="trackBase"/><div className="trackFill" style={{transform: `scaleX(${progress})`}}/>
          {[['Order placed', ShoppingBag], ['Preparing', Box], ['Dispatched', Truck], ['Delivered', PackageCheck]].map(([label, Icon], index) => {
            const active = progress >= index / 3;
            const I = Icon as React.ComponentType<{size?: number}>;
            return <div className={`milestone ${active ? 'active' : ''}`} key={label as string}><div><I size={22}/></div><b>{label as string}</b><small>{index === 0 ? '10:32 AM' : index === 1 ? '1:15 PM' : index === 2 ? 'Today' : 'Tomorrow'}</small></div>;
          })}
        </div>
        <div className="trackingFoot"><MapPin size={19}/><span>Delivery to Quezon City, Metro Manila</span><Clock3 size={19}/><span>Live status updates</span></div>
      </div>
    </AbsoluteFill>
  );
};

const Closing: React.FC = () => {
  const frame = useCurrentFrame();
  const {fps, durationInFrames} = useVideoConfig();
  return (
    <AbsoluteFill className="scene closing" style={{opacity: fade(frame, durationInFrames)}}>
      <div className="closingGlow"/>
      <div className="closingContent"><div style={rise(frame, fps)}><Brand light/></div><h2 style={rise(frame, fps, 5)}>Find it. Explore it.<br/><span>Make it yours.</span></h2><p style={rise(frame, fps, 10)}>A smarter furniture shopping experience, powered by FurniSync Ecommerce.</p><div className="closingCta" style={rise(frame, fps, 16)}>Start exploring <ChevronRight/></div><small style={rise(frame, fps, 22)}>FURNISYNC · CONNECTING FURNITURE, BUSINESS, AND PEOPLE</small></div>
    </AbsoluteFill>
  );
};

export const EcommercePromo: React.FC = () => (
  <AbsoluteFill style={{fontFamily, backgroundColor: '#fff'}}>
    <Sequence durationInFrames={180}><Opening/></Sequence>
    <Sequence from={180} durationInFrames={300}><Browse/></Sequence>
    <Sequence from={480} durationInFrames={330}><Explore3D/></Sequence>
    <Sequence from={810} durationInFrames={300}><Compare/></Sequence>
    <Sequence from={1110} durationInFrames={330}><Checkout/></Sequence>
    <Sequence from={1440} durationInFrames={210}><Track/></Sequence>
    <Sequence from={1650} durationInFrames={150}><Closing/></Sequence>
  </AbsoluteFill>
);
